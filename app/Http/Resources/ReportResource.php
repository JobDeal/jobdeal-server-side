<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
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
            "user" => $this->user,
            "job" => $this->job,
            "reportText" => $this->report_text,
            "createdAt" => Carbon::parse($this->created_at)->toDateTimeString()
        ];
    }
}
