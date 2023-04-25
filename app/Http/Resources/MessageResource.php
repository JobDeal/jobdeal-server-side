<?php

namespace App\Http\Resources;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class MessageResource extends JsonResource
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
            "body" => $this->body,
            "image" => $this->image,
            "seenAt" => $this->seen_at,
            'timePass' =>  $this->when(0 < 1, function (){
                if($this->updated_at != null){
                    return Carbon::now()->diffInMinutes(Carbon::parse($this->updated_at));
                } else {
                    return Carbon::now()->diffInMinutes(Carbon::parse($this->created_at));
                }
            }),
            "sender" => $this->when($this->sender, new UserResource($this->sender)),
            "conversation" => $this->when($this->conversation , new ConversationResource($this->conversation)),
        ];
    }
}
