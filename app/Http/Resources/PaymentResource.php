<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            "refId" => $this->ref_id,
            "status" => $this->status,
            "provider" => $this->provider,
            "type" => $this->type,
            "amount" => $this->amount,
            "currency" => $this->currency,
            "htmlSnippet" => $this->when($this->html_snippet, $this->html_snippet),
            "error" => $this->when($this->error, $this->error),
            "errorMessage" => $this->when($this->error_message, $this->error_message),
            "updatedAt" => $this->when($this->updated_at, Carbon::parse($this->updated_at)->toFormattedDateString()),
            "createdAt" => Carbon::parse($this->created_at)->toFormattedDateString(),
            "job" => $this->when($this->job_id, new JobResource($this->job)),
            "doer" => $this->when($this->doer_id, new UserResource($this->doer)),
            "user" => new UserResource($this->user)
        ];
    }
}
