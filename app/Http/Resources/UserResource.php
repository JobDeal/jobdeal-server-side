<?php

namespace App\Http\Resources;

use App\Http\Helper;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "surname" => $this->surname,
            "mobile" => $this->mobile,
            "email" => $this->email,
            "avatar" => $this->when($this->avatarImage, $this->avatarImage),
            "address" => $this->address,
            "zip" => $this->zip,
            "city" => $this->city,
            "country" => $this->country,
            "locale" => $this->locale,
            "active" => (bool) $this->active,
            "bankId" => $this->bank_id,
            "roleId" => $this->role_id,
            "subscription" => $this->subscription,
            "currency" => Helper::getCurrencyByCountry($this->country),
            "createdAt" => Carbon::parse($this->created_at)->toFormattedDateString(),
            "notificationCount" => $this->when($this->notificationCount, $this->notificationCount, 0),
            "rate" => $this->when($this->rate, $this->rate),
            "myInfo" => $this->when($this->myInfo, $this->myInfo),
            "activeJobs" => $this->activeJobs,
            "aboutMe" => $this->about_me
        ];
    }
}
