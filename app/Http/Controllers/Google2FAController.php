<?php

namespace App\Http\Controllers;

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
      $this->middleware('auth:api');
      $this->middleware('2fa', ['only' => ['store']]);
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

      // Save secret key to session for use in 'store' method
      $request->session()->flash('google_2fa_secret_key', $google2fa_secret);

      // Generate the QR image. This is the image the user will scan with their app
      // to set up two factor authentication
      $QR_Image = $google2fa->getQRCodeInline(
          config('app.name'),
          $user->email,
          $google2fa_secret
      );

      // return QR image and secret
      return response()->json(['QR_Image' => $QR_Image, 'secret' => $google2fa_secret], 201);
  }

  public function store(Request $request)
  {
      $user = Auth::guard('api')->user();
      if ($user == null) {
        $user = Auth::user();
      }

      $user->google2fa_secret = session('google_2fa_secret_key');
      $user->save();

      return response()->json($user, 201);
  }
}
