<?php

namespace App\Http\Resources;

use App\Http\Helper;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class JobPushResource extends JsonResource
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
            "user" => new UserPushResource($this->user),
            "name" => $this->name,
            "description" => substr($this->description, 0, 30) . '...',
            "price" => $this->price,
            "currency" => Helper::getCurrencyByCountry($this->country),
            //"address" => $this->address,
            "status" => $this->status,
            //"categoryId" => $this->category_id

        ];
    }
}
