<?php

namespace App\Http\Middleware;

use Closure;
use App\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChannelAuth
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
        $channel = $request->channel;
        if (!$channel instanceof Channel) {
          $channel = Channel::findOrFail($channel);
        }
        $permissions = explode('|', $permissions);

        foreach ($permissions as $key => $value) {
            if (!Channel::hasPermission($value, $user, $channel)) {
                abort(403, "Unauthorised action: ".$value);
            }
        }

        return $next($request);
    }
}
