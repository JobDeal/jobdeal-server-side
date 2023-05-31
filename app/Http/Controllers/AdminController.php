<?php

namespace App\Http\Controllers;

use App\Admin;
use App\Http\Helper;
use App\Http\Resources\UserResource;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function login(Request $request){
        $validator = Validator::make($request->json()->all(), [
            'email' => 'max:255|email|required',
            'password' => 'required|min:6',
        ]);


        if ($validator->fails()) {
            Log::debug($validator->messages()->first());
            return response($validator->messages()->first(), 470);
        }

        $user = Admin::where("email", "=", $request->json("email"))->first();

        if(!$user)
            return response(Helper::jsonError("User not found."), 470);

        if(Hash::check($request->json("password"), $user->password)){//check password hash
            $jwtUser = [];
            $jwtUser['id'] = $user->id;
            $jwtUser['email'] = $user->email;
            $jwtUser['expirationDate'] = time() * 1000 + (24 * 60 * 60 * 1000);

            $key = env("JWT_KEY");
            $jwt = JWT::encode($jwtUser, $key, 'HS256');

            Auth::login($user);

            $response["jwt"] = $jwt;
            $response["user"] = new UserResource($user);

            return response($response);
        } else {//invalid password
            return response(Helper::jsonError("Invalid password!"), 401);
        }
    }
}
