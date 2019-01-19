<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Google2FAController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct(Request $request)
  {
      $this->middleware('auth', ['except' => ['index', 'authenticate']]);
  }

  public function index()
  {
      return view('google2fa.index');
  }

  public function authenticate(Request $request)
  {
      if (!$request->session()->has('2fa:user:id')) {
        return redirect('/');
      }

      // Initialise the 2FA class
      $google2fa = app('pragmarx.google2fa');

      $user = User::findOrFail($request->session()->get('2fa:user:id'));
      $token = $request->input('one_time_password');
      if ($google2fa->verifyKey($user->google2fa_secret, $token)) {
          $request->session()->remove('2fa:user:id');

          auth()->loginUsingId($user->id);
          return redirect('/');
      }
      return redirect('/login/auth')->withErrors(['error' => __('Invalid Code')]);
  }

  public function create(Request $request)
  {
      $user = Auth::guard('api')->user();
      if ($user == null) {
        $user = Auth::user();
      }

      // Initialise the 2FA class
      $google2fa = app('pragmarx.google2fa');

      $google2fa_secret = $google2fa->generateSecretKey();

      $request->session()->flash('google_2fa_secret_key', $google2fa_secret);

      // Generate the QR image. This is the image the user will scan with their app
      // to set up two factor authentication
      $QR_Image = $google2fa->getQRCodeInline(
          config('app.name'),
          $user->email,
          $google2fa_secret
      );

      // return QR image and secret codes
      return response()->json(['qr_img' => $QR_Image, 'secret' => $google2fa_secret], 201);
  }

  public function store(Request $request)
  {
      $user = Auth::guard('api')->user();
      if ($user == null) {
        $user = Auth::user();
      }

      // Initialise the 2FA class
      $google2fa = app('pragmarx.google2fa');

      if ($google2fa->verifyKey(session('google_2fa_secret_key'), $request->input('verify_code'))) {
        $user->google2fa_secret = session('google_2fa_secret_key');
        $user->save();

        return response()->json($user, 201);
      } else {
        return response()->json("Invalid Verification Code", 400);
      }
  }

  public function remove(Request $request)
  {
      $user = Auth::guard('api')->user();
      if ($user == null) {
        $user = Auth::user();
      }

      // Initialise the 2FA class
      $google2fa = app('pragmarx.google2fa');

      if ($google2fa->verifyKey($user->google2fa_secret, $request->input('verify_code'))) {
        $user->google2fa_secret = null;
        $user->save();

        return response()->json($user, 201);
      } else {
        return response()->json("Invalid Authentication Code", 400);
      }
  }
}
