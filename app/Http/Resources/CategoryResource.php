<?php

namespace App\Http\Resources;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class CategoryResource extends JsonResource
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
            "parentId" => $this->parent_id,
            "totalSubCategoryCount" => $this->totalSubCategoryCount,
            //"name" => $this->getTranslation('name', 'se'),
            "name" => $this->name,
            "color" => $this->color,
            "image" => $this->image,
            "description" => $this->description,
            "subCategory" => CategoryResource::collection($this->subCategory),
            //"features" => $this->features,
        ];
    }
}
