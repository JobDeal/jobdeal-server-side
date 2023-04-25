<?php

namespace App\Http\Resources;

use App\User;
use App\Message;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;


class ConversationResource extends JsonResource
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
            "latestMessage" =>$this->when($this->latestMessage, $this->latestMessage->body),
            "sender" =>$this->when($this->sender,  new UserResource($this->sender)),
            "lastReceiver" => $this->when($this->lastReceiver, new UserResource($this->lastReceiver)),
            "seen" => $this->seen,
            "unread" => $this->when($this->unread, $this->unread),
            'timePass' =>  $this->when(0 < 1, function (){
                if($this->updated_at != null){
                    return Carbon::now()->diffInMinutes(Carbon::parse($this->updated_at));
                } else {
                    return Carbon::now()->diffInMinutes(Carbon::parse($this->created_at));
                }
            }),
        ];
    }
}
