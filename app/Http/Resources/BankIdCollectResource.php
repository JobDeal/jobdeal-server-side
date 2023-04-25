<?php

namespace App\Http\Resources;

use App\Http\Helper;
use Illuminate\Http\Resources\Json\JsonResource;

class BankIdCollectResource extends JsonResource
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
            "orderRef" => $this->orderRef,
            "status" => $this->status,
            "hint" => $this->when($this->hintCode, Helper::getHintForBankId($this->hintCode)),
            "data" => $this->when($this->status == 'complete', $this->completionData->user)
        ];
    }
}
