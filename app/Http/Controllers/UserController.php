<?php

namespace App\Http\Controllers;

use App\User;
use App\Events\UserUpdate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class UserController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct(Request $request)
  {
      $this->middleware('auth:api', ['except' => ['status_web']]);
      $this->middleware('webhook', ['only' => ['status_web']]);
  }

  /**
  * Fetch all users
  *
  * @return User
  */
  public static function index()
  {
      return User::all(User::$profile);
  }

  /**
  * Fetch single user
  *
  * @return User
  */
  public static function show(User $user)
  {
      return $user->get(User::$profile)->where('id', $user->id)->first();
  }

  /**
  * Update user status
  *
  * @return User
  */
  public static function status(User $user, $status)
  {
      if ($status !== "online" && $status !== "offline" && $status !== "away") {
        return response()->json("Not a valid status", 400);
      }

      $user->status = $status;
      $user->save();

      broadcast(new UserUpdate($user));

      return response()->json($user::get($user::$viewable)->where('id', $user->id)->first(), 200);
  }

  /**
  * Update user status from webhook
  *
  * @return User
  */
  public static function status_web(Request $request)
  {
      $data = json_decode(file_get_contents('php://input'), true);
      $user_id = (int)$data['events'][0]['user_id'];
      $user = User::findOrFail($user_id);
      switch ($data['events'][0]['name']) {
        case 'member_added': {
          $status = "online";
          break;
        }
        case 'member_removed': {
          $status = "offline";
          break;
        }
        default: {
          return response()->json("Not a valid status", 400);
          break;
        }
      }

      $user->status = $status;
      $user->save();

      broadcast(new UserUpdate($user));

      return $user::get($user::$profile)->where('id', $user->id)->first();
  }

  /**
  * Edit user attributes
  *
  * @param Request $request
  * @param User $user
  * @return Response
  */
  public static function update(Request $request, User $user)
  {
      $data = $request->all();
      foreach ($data as $key => $value) {
        if (!in_array($key, $user->editable) && !($key == 'password_old' || $key == 'password_confirmation')) {
          unset($data[$key]);
        }
      }

      if (isset($data['password_old']) || isset($data['password']) || isset($data['password_confirmation'])) {
        $request->validate([
            'password_old' => [
              'required',
              function ($attribute, $value, $fail) use ($user) {
                if (!Hash::check($value, $user->password)) {
                  $fail('Your current password is invalid.');
                }
              },
            ],
            'password' => 'required|string|min:6|confirmed',
         ]);

         unset($data['password_old']);
         unset($data['password_confirmation']);
         $data['password'] = Hash::make($data['password']);
      }

      if (isset($data['options'])) {
        $request->validate([
            'options' => [
              'required',
              'json',
              function ($attribute, $value, $fail) use ($user) {
                foreach (json_decode($value, true) as $key => $val) {
                  // Ensure the option is a valid user option
                  if (!in_array($val, $user->options)) {
                    $fail($val.' is not a valid option.');
                  }
                }
              },
            ],
         ]);
      }

      if ($request->hasFile('avatar')) {
        $request->validate([
            'avatar' => 'image|mimes:jpeg,png,jpg|max:5120'
         ]);

        $avatar = $request->file('avatar');
        $img = Image::make($avatar)->resize(256, 256);
        $img->stream();
        Storage::put('public/avatars/'.$user->id.'.png', $img);
      }

      $user->update($data);

      broadcast(new UserUpdate($user));

      return response()->json($user::get($user::$viewable)->where('id', $user->id)->first(), 200);
  }
}
