<?php

namespace App\Http\Middleware;

use Closure;
use App\Message;
use Illuminate\Http\Request;

class MessageAuth
{
  /**
   * Handle an incoming request.
   *
   * @param  $permissions
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   */
  public function handle($request, Closure $next, $permissions)
  {
      $user = $request->user();
      if ($user == null) {
        abort(403, "Unauthorised action");
      }
      $id = $request->message->message_id;
      $message = Message::where('message_id', $id)->first();
      $permissions = explode('|', $permissions);

      foreach ($permissions as $key => $value) {
          if (!$message::hasPermission($value, $user, $message)) {
              abort(403, "Unauthorised action: ".$value);
          }
      }

      return $next($request);
  }
}
