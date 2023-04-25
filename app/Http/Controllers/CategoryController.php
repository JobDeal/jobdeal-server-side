<?php

namespace App\Http\Controllers;

use App\Http\Helper;
use App\Http\Resources\CategoryResource;
use App\Category;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CategoryController extends Controller
{
    public function addCategory(Request $request){

        $category = new Category();
        $category->parent_id = $request->json('parentId');
        $category->name = $request->json('name');
        $category->color = $request->json('color');
        $category->image = $request->json('image');
        $category->description = $request->json('description');
        $category->save();

        return response(new CategoryResource($category));
    }

    public function getCategoryById($id){

        $category = Category::where("id", "=", $id)->first();

        if (!$category)
            return response(Helper::jsonError("Category not found."), 404);

        return response(new CategoryResource($category));
    }

    public function getAllCategories(Request $request, $root_id = null){

        $locale = $request->header('locale');

        if($locale == 'en'){
            app()->setLocale($locale);

        }else {
            app()->setLocale('se');
        }


        if ($root_id == 0)
            $root_id = null;

        $categories = Category::where("parent_id", "=", $root_id)->get();


        if(!$categories)
            return response(Helper::jsonError("Category not found."), 404);


        return response(CategoryResource::collection($categories));
    }

    public function deleteCategory($id){

        $category = Category::where("id", "=", $id)->first();

        if (!$category)
            return response(Helper::jsonError("Category not found."), 404);

        $category->deleted_at = Carbon::now()->toDateTimeString();
        $category->save();


        return response(Helper::jsonError("Category successfully deleted."), 200);

    }

    private function prepareCategoryTree($categories){

        $categoryArray = [];

        foreach ($categories as $category){

            if($category->parent_id === null) {
                $categoryArray[] = $category;
            } else {


                $parentCat = $categories->where('id','=',$category->parent_id)->first();
                if($parentCat->parent_id === null) {
                    $categoryArray[$parentCat->id][] = $category;
                } else {
                    $categoryArray[1][$category->parent_id][] = $category;
                }


            }

        }

        return $categoryArray;

    }
}
