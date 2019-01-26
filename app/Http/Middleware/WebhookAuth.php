<?php

namespace App\Http\Middleware;

use Closure;

class WebhookAuth
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   */
  public function handle($request, Closure $next)
  {
      $app_key = $request->header('X_PUSHER_KEY');
      $signature = $request->header('X_PUSHER_SIGNATURE');

      $app_secret = config('broadcasting.connections.pusher.secret');

      $expected = hash_hmac('sha256', file_get_contents('php://input'), $app_secret, false);

      if ($signature !== $expected) {
        abort(401, "Not authenticated: ".$app_key." | ".$signature);
      }

      return $next($request);
  }
}
