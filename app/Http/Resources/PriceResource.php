<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceResource extends JsonResource
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
            "list" => $this->list,
            "boost" => $this->boost,
            "choose" => $this->choose,
            "difference" => $this->difference,
            "subscribe" => $this->subscribe,
            "swishFee" => $this->swish_fee,
            "speedy" => $this->speedy,
            "country" => $this->country,
            "currency" => $this->currency,
            "fromDate" => Carbon::parse($this->from_date)->toDateString(),
            "createdAt" => Carbon::parse($this->created_at)->toDateString()
        ];
    }
}
