<?php

namespace App\Http\Controllers;

use App\Device;
use App\Http\Helper;
use App\Http\Resources\DeviceResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DevicesController extends Controller
{
    /**
     * @SWG\Post(
     *     path="/user/device/add",
     *     summary="Add device to the user",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="token", type="string"),
     *             @SWG\Property(property="type", type="string"),
     *             @SWG\Property(property="appVersion", type="string"),
     *             @SWG\Property(property="model", type="string"),
     *             @SWG\Property(property="osVersion", type="string"),
     *         ),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function addDevice(Request $request){
        $validator = Validator::make($request->json()->all(), [
            'token' => 'required',
            'type' => 'required',
            'appVersion' => 'required'
        ]);


        if ($validator->fails()) {
            return response(Helper::jsonError($validator->messages()->first()), 400);
        }

        $device = Device::where("user_id", "=", Auth::user()->id)->first();

        if(!$device)
            $device = new Device();

        $device->user_id = Auth::user()->id;
        $device->token = $request->json("token");
        $device->type = $request->json("type");
        $device->app_version = $request->json("appVersion");
        $device->model = $request->json("model");
        $device->os_version = $request->json("osVersion");
        $device->save();

        return response(new DeviceResource($device));
    }
}
