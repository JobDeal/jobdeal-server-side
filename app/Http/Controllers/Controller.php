<?php

namespace App\Http\Controllers;

use App\Http\Helper;
use App\Http\NotificationConst;
use App\Http\PayConst;
use App\Jobs\CreateAndSendInvoice;
use App\Jobs\FindExpiredJobs;
use App\Jobs\PushNotification;
use App\User;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * @OA\OpenAPI(
 *     basePath="/api",
 *     schemes={"http", "https"},
 *     host=L5_SWAGGER_CONST_HOST,
 *     @OA\Info(
 *         version="1.0.0",
 *         title="L5 Swagger API",
 *         description="L5 Swagger API description",
 *         @OA\Contact(
 *             email="saitokdev@gmail.com"
 *         ),
 *     ),
 *     @OA\SecurityScheme(
 *         securityDefinition="bearer_token",
 *         type="apiKey",
 *         name="Authorization",
 *         in="header"
 *     )
 * )
 */

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="L5 Swagger API",
 *     description="L5 Swagger API description",
 *     @OA\Contact(
 *         email="saitokdev@gmail.com"
 *     ),
 * ),
*/
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function testNotification(\Request $request, $userId){
        $user = User::where("id", "=", $userId)->first();

        if(!$user)
            return response(Helper::jsonError("User not found!"), 404);

        PushNotification::dispatch($user->id, $senderId = -1, NotificationConst::Rate, $user->id, [], "Description test", "Title TEST", $sendNotification = true);
    }

    public function findExpiredJobs() {
        FindExpiredJobs::dispatch();
    }

    public function testInvoice(){
        CreateAndSendInvoice::dispatchNow(868, 1, PayConst::paymentProcessorSwish);

        return "OK";
    }
}
