<?php

namespace App\Http\Resources;

use App\Http\Helper;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            'id' => $this->id,
            'userId' => $this->user_id,
            'fromId' => $this->from_id,
            'type' => $this->type,
            'job' => $this->when($this->job, new JobResource($this->job)),
            'rate' => $this->when($this->rate, new RateResource($this->rate)),
            'sender' => new UserResource($this->sender),
            'title' => Helper::getTitleForNotificationType($this->type),
            'body' => Helper::getBodyForNotificationType($this->type),
            'isSeen' => (bool) $this->shown,
            'unreadCount' => $this->unreadCount,
            'timePass' =>  $this->when(0 < 1, function (){
                return Carbon::now()->diffInMinutes(Carbon::parse($this->created_at)) + 1;
            })
        ];
    }
}
