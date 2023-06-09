<?php

namespace App\Http\Controllers;

use App\Applicant;
use App\Bookmark;
use App\Device;
use App\Http\Helper;
use App\Http\JobConst;
use App\Http\Resources\BankIdCollectResource;
use App\Http\Resources\BankIdResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\VerificationResource;
use App\Job;
use App\Notification;
use App\Rate;
use App\Report;
use App\User;
use App\Verification;
use App\Wishlist;
use Carbon\Carbon;

//use Grimzy\LaravelMysqlSpatial\Types\Point;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

//use Symfony\Component\Console\Helper\Helper;


class UserController extends Controller
{
    /**
     * @SWG\Post(
     *     path="/user/login",
     *     summary="Login, generate auth token",
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="email", type="string"),
     *             @SWG\Property(property="password", type="string"),
     *             @SWG\Property(property="country", type="string"),
     *             @SWG\Property(property="locale", type="string"),
     *             @SWG\Property(property="timezone", type="string")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function login(Request $request){
        $validator = Validator::make($request->json()->all(), [
            'email' => 'max:255|email|required',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            Log::debug($validator->messages()->first());
            return response($validator->messages()->first(), 470);
        }

        $user = User::where("email", "=", $request->json("email"))->first();

        if(!$user)
            return response(Helper::jsonError("User not found."), 470);

        $country = $request->json("country", "se");
        $locale = $request->json("locale", "en");
        $timezone = $request->json("timezone", null);

        if (trim($country) == "" || strlen($country) > 3 || !$timezone) { // nema zemlje iz nekog razloga, pozovi ip json i pribavi zemlju
           try {
               $client = new \GuzzleHttp\Client([]);
               $res = $client->request('GET', 'http://ip-api.com/json/' . \request()->ip(), []);

               if ($res->getStatusCode() == 200) {
                   $country = strtolower(json_decode($res->getBody(), true)["countryCode"]);
                   $timezone = strtolower(json_decode($res->getBody(), true)["timezone"]);
               } else {
                   Log::error("Country fail!");
                   return response("Country fail", 404);
               }
           } catch (\Exception $e){
               Log::info($e->getMessage());
           }
        }

        if ($country != $user->country) {
            $user->country = $country;
            $user->save();
        }

        if ($locale != $user->Locale && $locale) {
            $user->Locale = $locale;
            $user->save();
        }

        if($timezone != null && $user->timezone != $timezone){
            $user->timezone = $timezone;
            $user->save();
        }


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
            $response["info"] = Helper::getInfo($user);
            $response["prices"] = Helper::getPrices();
            $response['wishlist'] = Helper::getWishList();

            return response($response);
        } else {//invalid password
            return response(Helper::jsonError("Invalid password!"), 401);
        }
    }

    /**
     * @SWG\Post(
     *     path="/user/register",
     *     summary="Register",
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="email", type="string"),
     *             @SWG\Property(property="password", type="string"),
     *             @SWG\Property(property="name", type="string"),
     *             @SWG\Property(property="surname", type="string"),
     *             @SWG\Property(property="mobile", type="string"),
     *             @SWG\Property(property="address", type="string"),
     *             @SWG\Property(property="zip", type="string"),
     *             @SWG\Property(property="city", type="string"),
     *             @SWG\Property(property="country", type="string"),
     *             @SWG\Property(property="locale", type="string"),
     *             @SWG\Property(property="timezone", type="string"),
     *             @SWG\Property(property="uid", type="string"),
     *             @SWG\Property(property="roleId", type="string"),
     *             @SWG\Property(property="bankId", type="string"),
     *             @SWG\Property(property="aboutMe", type="string")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function register(Request $request){
        $validator = Validator::make($request->json()->all(), [
            'email' => 'max:255|email|required',
            'password' => 'required|min:6',
            'name' => 'required',
            'surname' => 'required',
            'mobile' => 'required',
            'address' => 'required',
            'zip' => 'required',
            'city' => 'required',
            'uid' => 'required'
            //'bankId' => 'required' disabled for now
        ]);


        if ($validator->fails()) {
            return response($validator->messages()->first(), 400);
        }

        //check if email already exists
        $exists = User::where("email", "=", $request->json("email"))->first();

        if($exists){
            return response(Helper::jsonError("E-Mail already exists"), 470);
        }

        $country = $request->json("country", "se");
        $locale = $request->json("locale", "en");
        $timezone = $request->json("timezone", null);

        // if (trim($country) == "" || strlen($country) > 3 || !$timezone) { // nema zemlje iz nekog razloga, pozovi ip json i pribavi zemlju
        //     $client = new \GuzzleHttp\Client([]);
        //     $res = $client->request('GET', 'http://ip-api.com/json/' . \request()->ip(), []);

        //     if ($res->getStatusCode() == 200) {
        //         $country = strtolower(json_decode($res->getBody(), true)["countryCode"]);
        //         $timezone = strtolower(json_decode($res->getBody(), true)["timezone"]);
        //     } else {
        //         Log::error("Country fail!");
        //         return response("Country fail", 404);
        //     }
        // }

        $user = new User();
        $user->email = $request->json("email");
        $user->name = $request->json("name");
        $user->surname = $request->json("surname");

        // $verification = Verification::where('uid', '=', $request->json('uid'))->first();

        // if (!$verification) {
        //     return response(Helper::jsonError('Verification not found'), 404);
        // }

        // if ($verification->phone != $request->json("mobile")) {
        //     return response(Helper::jsonError("Verification phone different than registration phone"), 410);
        // }

        $user->mobile = $request->json("mobile");
        $user->avatar = $request->json("avatar");
        $user->address = $request->json("address");
        $user->zip = $request->json("zip");
        $user->city = $request->json("city");
        $user->role_id = $request->json("roleId");
        $user->country = $country;
        $user->locale = $locale;
        $user->timezone = $timezone;
        $user->bank_id = $request->json("bankId", null);

        if ($request->json('aboutMe')) {
            $user->about_me = $request->json('aboutMe');
        }

        $user->password = Hash::make($request->json("password"));
        //$user->location = new Point(1,1);
        $user->save();

        $jwtUser = [];
        $jwtUser['id'] = $user->id;
        $jwtUser['email'] = $user->email;
        $jwtUser['expirationDate'] = time() * 1000 + (24 * 60 * 60 * 1000);

        $key = env("JWT_KEY");
        $jwt = JWT::encode($jwtUser, $key, 'HS256');

        Auth::login($user);

        $response["jwt"] = $jwt;
        $response["user"] = new UserResource($user);
        $response["info"] = Helper::getInfo($user);
        $response['wishlist'] = Helper::getWishList();

        //$this->sendVerificationMail($user->id);

        return response($response);
    }

    /**
     * @SWG\Get(
     *     path="/user/get/{userId}",
     *     summary="Get a user",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="path",
     *         name="userId",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function getUserById($userId){
        $user = User::where("id", "=", $userId)->first();

        if(!$user){
            return response(Helper::jsonError("User not found."), 404);
        }

        if(Auth::check() && $user->id == Auth::user()->id) {
            $doerJobsDone = Applicant::where("user_id", "=", $user->id)->where("choosed_at", "!=", null)->count();
            $doerEarned = Applicant::where("user_id", "=", $user->id)->where("choosed_at", "!=", null)->sum("price");

            $finishedJobs = Job::where("user_id", "=", $user->id)
                ->where("expire_at", "<", Carbon::now()->toDateTimeString())
                ->where("status", "=", JobConst::StatusDoerChoosed)
                ->get();

            $buyerSpent = 0;
            $buyerContracts = 0;//Job::where("user_id", "=", Auth::user()->id)->where("is_active", "=", 1)->where("expire_at", ">=", Carbon::now()->toDateTimeString())->count();

            foreach ($finishedJobs as $job) {
                foreach ($job->applicants as $applicant) {
                    if ($applicant->choosed_at != null) {
                        $buyerSpent += $applicant->price;
                        $buyerContracts++;
                    }
                }
            }


            $res = array();
            $res["doerJobsDone"] = $doerJobsDone;
            $res["doerEarned"] = $doerEarned;
            $res["buyerSpent"] = $buyerSpent;
            $res["buyerContracts"] = $buyerContracts;


            $user->myInfo = $res;
        }


        return response(new UserResource($user));
    }

    /**
     * @SWG\Get(
     *     path="/panel/user/get/{page}",
     *     summary="Get users list",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="path",
     *         name="page",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function getUserList($page){
        $users = User::where("id", ">", 0)->limit(100)->offset($page * 100)->get();

        return response(UserResource::collection($users));
    }

    /**
     * @SWG\Post(
     *     path="/user/update",
     *     summary="Update user",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="email", type="string"),
     *             @SWG\Property(property="password", type="string"),
     *             @SWG\Property(property="name", type="string"),
     *             @SWG\Property(property="surname", type="string"),
     *             @SWG\Property(property="mobile", type="string"),
     *             @SWG\Property(property="address", type="string"),
     *             @SWG\Property(property="zip", type="string"),
     *             @SWG\Property(property="city", type="string"),
     *             @SWG\Property(property="country", type="string"),
     *             @SWG\Property(property="locale", type="string"),
     *             @SWG\Property(property="timezone", type="string"),
     *             @SWG\Property(property="uid", type="string"),
     *             @SWG\Property(property="roleId", type="string"),
     *             @SWG\Property(property="bankId", type="string"),
     *             @SWG\Property(property="aboutMe", type="string")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function updateUser(Request $request){
        $validator = Validator::make($request->json()->all(), [
            'email' => 'max:255|email|required',
            'name' => 'required',
            'surname' => 'required',
            'mobile' => 'required',
            'address' => 'required',
            'zip' => 'required',
            'city' => 'required',
            'country' => 'required'
        ]);


        if ($validator->fails()) {
            return response($validator->messages()->first(), 470);
        }
        $user = User::where("id", "=", Auth::user()->id)->first();

        if(!$user)
            return response(Helper::jsonError("User not found."), 404);

        if($user->email != $request->json("email")) {
            $exists = User::where("email", "=", $request->json("email"))->first();

            if($exists)
                return response(Helper::jsonError("E-Mail already exists."), 400);

            $user->email = $request->json("email");
        }

        if(substr( $request->json("avatar"), 0, 4 ) != "http") {
            $user->avatar = $request->json("avatar");
        }

        $user->name = $request->json("name");
        $user->surname = $request->json("surname");

        if (key_exists('aboutMe', $request->all())) {
            $user->about_me = $request->json('aboutMe');
        }

        // if ($request->json("mobile") != $user->mobile) {

        //     $verification = Verification::where('uid', '=', $request->json('uid'))->first();

        //     if (!$verification) {
        //         return response(Helper::jsonError('Verification not found'), 404);
        //     }

        //     if ($verification->phone != $request->json("mobile")) {
        //         return response(Helper::jsonError("Verification phone different than registration phone"), 410);
        //     }

        // }

        $user->mobile = $request->json("mobile");
        $user->address = $request->json("address");
        $user->zip = $request->json("zip");
        $user->city = $request->json("city");
        $user->bank_id = $request->json("bankId");
        $user->about_me = $request->json('aboutMe', null);


        $user->save();

        return response(new UserResource($user));
    }

    public function checkEmail(Request $request){
        $exists = User::where("email", "=", $request->json("email"))->first();

        if($exists){
            $result["result"] = "true";
            return response($result);
        } else {
            $result["result"] = "false";
            return response($result);
        }
    }

    /**
     * @SWG\Post(
     *     path="/user/password",
     *     summary="Update password",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="oldPassword", type="string"),
     *             @SWG\Property(property="newPassword", type="string")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function changePassword(Request $request){
        $oldPassword = $request->json('oldPassword');
        $newPassword = $request->json('newPassword');

        $user = User::where("id", "=", Auth::user()->id)->first();

        if(!$user)
            return response(Helper::jsonError("User not found."), 404);

        if(Hash::check($oldPassword, $user->password)){
            $user->password = Hash::make($newPassword);
            $user->save();
        } else {
            return response(Helper::jsonError("Invalid password!"), 405);
        }

        return response(new UserResource($user));
    }

    /**
     * @SWG\Post(
     *     path="/user/verify/{token}",
     *     summary="Verify email",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="path",
     *         name="token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function verifyEmail($token){
        $user = User::where("remember_token", "=", $token)->first();

        if($user){
            return redirect("http://jobdeal.justraspberry.com/verifyEmail");
        } else {
            return response("E-Mail verification failed!");
        }
    }

    public function sendVerificationMail($userId){
        $user = User::where("id", "=", $userId)->first();

        if(!$user)
            return response(Helper::jsonError("User not found."), 404);

        $user->remember_token = base64_encode($user->id . "|" . Carbon::now()->toDateTimeString());
        $user->save();

        Mail::raw('Click on link to verify email address: ' . $user->remember_token, function($message) use ($user)
        {
            $message->from('no-replay@jobdeal.eu', 'Job Deal');

            $message->to($user->email);
            $message->subject("Job Deal - Verify e-mail address");

        });
    }

    /**
     * @SWG\Post(
     *     path="/forgot-password/{token}",
     *     summary="Web Reset password",
     *     @SWG\Parameter(
     *         in="path",
     *         name="token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function webResetPassword($token){
        $user = User::where('remember_token', $token)->first();
        return view('users.resetpassword')->with(compact('user', 'token'));
    }

    /**
     * @SWG\Post(
     *     path="/user/resetpassword",
     *     summary="Reset password",
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="token", type="string"),
     *             @SWG\Property(property="password", type="string")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function resetPassword(Request $request){
        $token = $request->get("token");
        $password = $request->get("password");

        $tokenElements = explode("|", base64_decode($token));

        $userId = $tokenElements[0];
        $time = $tokenElements[1];

        if (Carbon::now()->diffInHours(Carbon::parse($time)) > 2){
            return response("Time for reseting password expired!", 400);
        }

        $user = User::where("id", "=", $userId)->first();

        $user->password = Hash::make($password);
        $user->remember_token = "";
        $user->save();

        return response("OK", 200);
    }

    /**
     * @SWG\Post(
     *     path="/user/password/forgot",
     *     summary="Forgot password",
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="email", type="string")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function forgotPassword(Request $request){
        $user = User::where("email", "=", $request->json("email"))->first();

        if(!$user)
            return response(Helper::jsonError("User not found."), 404);

        $user->remember_token = base64_encode($user->id . "|" . Carbon::now()->toDateTimeString());
        $user->save();

        Mail::raw(__("lang.forgot_password_desc", ["link" => "https://dev.jobdeal.com/forgot-password/" . $user->remember_token], $user->locale)
            , function ($message) use ($user) {
            $message->to($user->email)->subject(__("lang.forgot_password", [], $user->locale));
            $message->from('jobdeal.info@gmail.com', 'JobDeal');

        });

        return response("OK");
    }

    public function uploadImage(Request $request){
        $validator = Validator::make($request->all(), [
            'image' => 'required',
        ]);

        if ($validator->fails()) {
            return response("no_image", 400);
        }

        $path = $request->file('image')->store('avatars', ['disk' => 's3']);

        $response["fullUrl"] = Storage::disk("s3")->url($path);
        $response["path"] =  Storage::disk("s3")->path($path);

        return  response($response);
    }

    /**
     * @SWG\Get(
     *     path="/user/logout",
     *     summary="Logout",
     *     security={{"bearer_token":{}}},
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $device = Device::where("user_id", "=", Auth::user()->id)->first();

        if($device){
            $device->token = null;
            $device->save();
        }

        return response("{}");
    }

    /**
     * @SWG\Delete(
     *     path="/user/delete",
     *     summary="Delete account",
     *     security={{"bearer_token":{}}},
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function deleteAccount(Request $request){
        $user = User::where('id', Auth::user()->id)->first();

        if(!$user)
            return response(Helper::jsonError("User not found!"), 404);

        Applicant::where('user_id', $user->id)->delete();
        Bookmark::where('user_id', $user->id)->delete();
        Device::where('user_id', $user->id)->delete();
        Notification::where('from_id', $user->id)->delete();
        Notification::where('user_id', $user->id)->delete();
        Rate::where('user_id', $user->id)->delete();
        Report::where('user_id', $user->id)->delete();
        Wishlist::where('user_id', $user->id)->delete();

        Job::where('user_id', $user->id)->delete();
        $user->delete();

        return response("{}");
    }


    //----------------------------------BANK ID---------------------------------
    /**
     * @SWG\Post(
     *     path="/bankid/auth",
     *     summary="Bank ID Auth",
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="ip", type="string")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function bankIdAuth(){
        //https://appapi2.test.bankid.com/rp/v5

        $client = new Client();

        $body = [
            "endUserIp" => \request()->ip()
        ];

        $res = $client->request("post", "https://appapi2.test.bankid.com/rp/v5/auth", [
            "cert" => [storage_path("app/bankid.pem"), "qwerty123"],
            //'ssl_key' => [storage_path("app/bankid_key.key"), 'qwerty123'],
            "json" => $body,
            'verify' => false,
        ]);

        $bankIdRes = json_decode($res->getBody()->getContents(), true);

        return response(new BankIdResource($bankIdRes));
    }

    /**
     * @SWG\Post(
     *     path="/bankid/collect",
     *     summary="Bank ID Collect",
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="orderRef", type="string")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function bankIdCollect(Request $request)
    {
        $client = new Client();

        $body = [
            "orderRef" => $request->json("orderRef")
        ];

        try {
            $res = $client->request("post", "https://appapi2.test.bankid.com/rp/v5/collect", [
                "cert" => [storage_path("app/bankid.pem"), "qwerty123"],
                "json" => $body,
                'verify' => false,
            ]);
        } catch (\Exception $exception) {
            Log::debug("Collect Exception");
            return response(Helper::jsonError("BankId Error!"), 400);
        }

        if ($res->getStatusCode() == 200)
            $bankIdRes = json_decode((string)$res->getBody(), false);
        else
            return response(Helper::jsonError("BankId Error! Status code not right!"), 400);

        if (!property_exists($bankIdRes, "hintCode"))
            $bankIdRes->hintCode = null;

        if (!property_exists($bankIdRes, "completionData"))
            $bankIdRes->completionData = null;

        if (!property_exists($bankIdRes, "completionData"))
            $bankIdRes->completionData->user = null;

        sleep(1); // :)

        return response(new BankIdCollectResource($bankIdRes));
    }

    /**
     * @SWG\Post(
     *     path="/verification/send",
     *     summary="Request Verification",
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="mobile", type="string"),
     *             @SWG\Property(property="locale", type="string")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function requestVerification(Request $request) {

        $validator = Validator::make($request->json()->all(), [
            'mobile' => 'max:13|min:9|required'
        ]);

        if ($validator->fails()) {
            return response(Helper::jsonError($validator->messages()->first()), 400);
        }
        $code = mt_rand(100000, 999999);


        $twilioSid = config("conf.twilio_sid");
        $twilioAuth = config("conf.twilio_token");
        $twilioNumber = config("conf.twilio_number");



        $locale = $request->json('locale', 'en');

        $message = __("lang.verificationSms", ['code' => $code], $locale);


        try {

            $client = new \Twilio\Rest\Client($twilioSid, $twilioAuth);
            $response = $client->messages->create($request->json('mobile'),
                ['from' => $twilioNumber, 'body' => $message]);


            $verification = new Verification();
            $verification->phone = $request->json('mobile');
            $verification->provider = 'Twilio';
            $verification->code = $code;
            $verification->status = $response->status;
            $verification->message_id = $response->sid;
            $verification->uid = null;
            $verification->save();

            return response(new VerificationResource($verification));

        } catch (\Exception $e) {
            Log::warning($e->getTraceAsString());
            Log::warning($e->getMessage());
            return response(Helper::jsonError('Not possible to send verification.'), 410);
        }
    }

    /**
     * @SWG\Post(
     *     path="/verification/verify",
     *     summary="Verify code",
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="mobile", type="string"),
     *             @SWG\Property(property="code", type="string"),
     *             @SWG\Property(property="id", type="string")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function verifyCode(Request $request) {

        $validator = Validator::make($request->json()->all(), [
            'mobile' => 'max:13|min:9|required',
            'code' => 'required',
            'id' => 'required',

        ]);

        if ($validator->fails()) {
            return response(Helper::jsonError($validator->messages()->first()), 400);
        }

        $verification = Verification::where('id', '=', $request->json('id'))->orderBy('created_at', 'desc')->first();


        if (!$verification) {
            return response(Helper::jsonError('Verification not found'), 404);
        }

        if ($verification->created_at <= Carbon::now()->subMinutes(30)) {
            return response(Helper::jsonError('Verification expired'), 470);
        }


        Log::warning("verificationCode   ".$verification->code."  code  ".(string)$request->json('code'));

        if ($verification->code !== (string)$request->json('code')) {
            return response(Helper::jsonError('Code not valid'), 400);
        }

        if (!is_null($verification->uid)) {
            return response(Helper::jsonError('Verification already confirmed'), 400);
        }

        if ($verification->phone !== $request->json('mobile')) {
            return response(\App\Http\Resources\Helper::jsonError('Verification id and phone are not the same.'), 400);
        }

        $verification->status = 0;
        $verification->uid = Str::uuid();

        $verification->save();

        return response(new VerificationResource($verification));

    }

    /**
     * @SWG\Post(
     *     path="/user/location/update",
     *     summary="Update location",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="lat", type="string"),
     *             @SWG\Property(property="lng", type="string"),
     *             @SWG\Property(property="id", type="string")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function saveLocation(Request $request) {

        if ($request->has('lat') && $request->has('lng')) {
            $user = User::where('id', '=', Auth::user()->id)->first();

            if ($user) {
                $user->lat = $request->json('lat');
                $user->lng = $request->json('lng');
                $user->save();
            }
        }

        if ($request->has('lat') && $request->has('lon')) { // for ios, they send lon
            $user = User::where('id', '=', Auth::user()->id)->first();

            if ($user) {
                $user->lat = $request->json('lat');
                $user->lng = $request->json('lon');
                $user->save();
            }
        }

        return response("{}");
    }
}
