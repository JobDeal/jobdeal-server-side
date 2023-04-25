<?php

namespace App\Http\Middleware;

use App\Http\Helper;
use App\User;
use Carbon\Carbon;
use Closure;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class JWTMiddle
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
        /*
         * The app sends the JWT token without "Bearer ".
         * The backend previously didn't read the Bearer properly so this is a temporary solution
         * until the app has been corrected. Once that's correct we should get the bearer token
         * in a standard way.
         */
        $authHeader = $request->header("Authorization");
        $token = substr($authHeader, 0, 6) === "Bearer" ? $request->bearerToken() : $authHeader;

        $key = env("JWT_KEY");

        if(!$token || empty($token))
            return response(Helper::jsonError("Authorization missing!"), 401, []);

        JWT::$leeway = 180 * 60 * 24;
        $decoded = JWT::decode($token, $key, array('HS256'));

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
