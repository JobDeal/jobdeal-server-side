<?php

namespace App\Http\Resources;

use App\Http\Helper;
use App\User;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use function Zend\Diactoros\normalizeUploadedFiles;

class JobResource extends JsonResource
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
            "category" => $this->when($this->category, new CategoryResource($this->category)),
            "isBoost" => (bool) $this->is_boost,
            "isSpeedy" => (bool) $this->is_speedy,
            "isDelivery" => (bool) $this->is_delivery,
            "helpOnTheWay" => (bool) $this->help_on_the_way,
            "isBookmarked" => (bool) $this->isBookmark,
            "isListed" => (bool) $this->is_listed, //is buyer pay for list of doers
            "isApplied" => (bool) $this->isApplied,
            "isChoosed" => (bool) $this->isChoosed,
            "isExpired" => (bool) $this->isExpired,
            "isUnderbidderListed" => (bool) $this->is_underbidder_listed,
            "property" => $this->property,
            "images" => $this->allImages,
            "mainImage" =>  $this->when($this->mainImage, $this->mainImage, ""),
            "latitude" => $this->location->latitude,
            "longitude" => $this->location->longitude,
//            "latitude" => 1,
//            "longitude" => 1,
            "distance" => $this->when($this->distance, round($this->distance)),
            "country" => $this->country,
            "expireAt" => Carbon::parse($this->expire_at)->diffForHumans(Carbon::now(), true, true, 3),
            "expireAtDate" => Carbon::parse($this->expire_at)->toDateString(),
            "createdAt" => Carbon::parse($this->created_at)->toFormattedDateString(),
            "activeJobs" => $this->activeJobs,
            "bidCount" => $this->bidCount,
            "applicantCount" => $this->applicant_count,
            "choosedCount" => $this->choosedCount,
            "applicants" => ApplicantResource::collection($this->applicants()->where("choosed_at", "!=", null)->get())
        ];
    }
}
