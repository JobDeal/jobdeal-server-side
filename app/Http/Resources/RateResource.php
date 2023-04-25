<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class RateResource extends JsonResource
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
            "from" => new UserResource($this->user),
            "rate" => (float) $this->rate,
            "comment" => $this->comment,
            "job" => new JobResource($this->job),
            "createdAt" => Carbon::parse($this->created_at)->toFormattedDateString()
        ];
    }
}
