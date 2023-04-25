<?php

namespace App\Http\Resources;

use App\Http\Helper;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class JobLiteResource extends JsonResource
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
            "user" => new UserResource($this->user),
            "name" => $this->name,
            "description" => $this->description,
            "price" => $this->price,
            "currency" => Helper::getCurrencyByCountry($this->country),
            "address" => $this->address,
            "status" => $this->status,
            "categoryId" => $this->category_id,
            "isBoost" => (bool) $this->is_boost,
            "isSpeedy" => (bool) $this->is_speedy,
            "isBookmarked" => (bool) $this->isBookmark,
            "isListed" => (bool) $this->is_listed, //is buyer pay for list of doers
            "isApplied" => (bool) $this->isApplied,
            "isChoosed" => (bool) $this->isChoosed,
            "isExpired" => (bool) $this->isExpired,
            "isUnderbidderListed" => (bool) $this->is_underbidder_listed,
            "property" => $this->property,
            "images" => $this->allImages,
            "mainImage" =>  $this->when($this->mainImage, $this->mainImage, ""),
            "latitude" => $this->location->getLat(),
            "longitude" => $this->location->getLng(),
            "distance" => $this->when($this->distance, round($this->distance)),
            "country" => $this->country,
            "expireAt" => Carbon::parse($this->expire_at)->diffForHumans(Carbon::now(), true, true, 3),
            "createdAt" => Carbon::parse($this->created_at)->toFormattedDateString(),
            "bidCount" => $this->bidCount,
            "applicantCount" => $this->applicant_count,
        ];
    }
}
