<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
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
            'type' => $this->type,
            'token' => $this->token,
            'appVersion' => $this->app_version,
            'osVersion' => $this->os_version,
            'updatedAt' => $this->when($this->updated_at, Carbon::parse($this->updated_at)->toFormattedDateString()),
            'createdAt' => Carbon::parse($this->created_at)->toFormattedDateString(),
        ];
    }
}
