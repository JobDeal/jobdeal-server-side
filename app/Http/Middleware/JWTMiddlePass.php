<?php

namespace App\Http\Middleware;

use App\Http\Helper;
use App\User;
use Carbon\Carbon;
use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class JWTMiddlePass
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
            return $next($request);

        JWT::$leeway = 180 * 60 * 24;
        $decoded = JWT::decode($token, new Key($key, 'HS256'));

        $expire = Carbon::createFromTimestamp($decoded->expirationDate / 1000);
        $now = Carbon::now();

        if($now->gt($expire))
            return response("Authorization token has expired!", 401);

        $user = User::where('id','=',$decoded->id)->first();

        if(!$user)
            return response("User not found", 404, []);

        App::setLocale($user->locale);
        Auth::login($user);

        return $next($request);
    }
}
