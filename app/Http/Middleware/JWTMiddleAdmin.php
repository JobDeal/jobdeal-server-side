<?php

namespace App\Http\Middleware;

use App\Http\Helper;
use App\User;
use Carbon\Carbon;
use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Auth;

class JWTMiddleAdmin
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
        $token = $request->header("Authorization");

        $key = env("JWT_KEY");

        if(!$token || empty($token))
            return response(Helper::jsonError("Authorization missing!"), 401, []);

        JWT::$leeway = 60 * 60 * 24;
        $decoded = JWT::decode($token, new Key($key, 'HS256'));

        $expire = Carbon::createFromTimestamp($decoded->expirationDate / 1000);
        $now = Carbon::now();

        if($now->gt($expire))
            return response(Helper::jsonError("Authorization token has expired!"), 401);

        $user = User::where('Id','=',$decoded->id)->first();

        if(!$user)
            return response(Helper::jsonError("User not found"), 404, []);

        if($user->role_id != 1)
            return response(Helper::jsonError("Not admin!"), 401);

        Auth::login($user);

        return $next($request);
    }
}
