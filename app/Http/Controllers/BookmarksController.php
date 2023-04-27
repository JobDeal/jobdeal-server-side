<?php

namespace App\Http\Controllers;

use App\Bookmark;
use App\Http\Helper;
use App\Http\Resources\BookmarkResource;
use App\Http\Resources\JobResource;
use App\Job;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookmarksController extends Controller
{
    /**
     * @SWG\Post(
     *     path="/bookmark/add",
     *     summary="Add a bookmark",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="id", type="integer", description="Job ID")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function addBookmark(Request $request){
        $job = Job::where("id", "=", $request->json("id"))->first();

        if(!$job)
            return response(Helper::jsonError("Job not found!"), 404);

        $exists = Bookmark::where("user_id", "=", \Auth::user()->id)->where("job_id", "=", $job->id)->first();

        if($exists)
            return response(new JobResource($job));

        $bookmark = new Bookmark();
        $bookmark->user_id = \Auth::user()->id;
        $bookmark->job_id = $job->id;
        $bookmark->save();

        return response(new JobResource($job));
    }

    /**
     * @SWG\Delete(
     *     path="/bookmark/remove",
     *     summary="Remove a bookmark",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="id", type="integer", description="Job ID")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function removeBookmark(Request $request){
        $job = Job::where("id", "=", $request->json("id"))->first();

        if(!$job)
            return response(Helper::jsonError("Job not found!"));

        $bookmark = Bookmark::where("user_id", "=", \Auth::user()->id)->where("job_id", "=", $job->id)->first();

        if(!$bookmark)
            return response(Helper::jsonError("Bookmark doesn't exists!"), 404);

        $bookmark->delete();

        return response(new BookmarkResource($bookmark));
    }

    /**
     * @SWG\Get(
     *     path="/bookmark/all/{page}",
     *     summary="Get all categories",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="path",
     *         name="page",
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function listBookmarks($page = 0){
        $bookmarkIds = Bookmark::where("user_id", "=", \Auth::user()->id)->orderBy("created_at", "DESC")->limit(20)->offset(20 * $page)->get()->pluck("job_id");
        $user = User::where("id", "=", \Auth::user()->id)->first();

        //selectRaw('*, ST_Distance_Sphere(Point(' . $user->location->getLng() . ',' . $user->location->getLat() . '), location) as distance')

        $jobs = Job::where("expire_at", ">=", Carbon::now()->toDateTimeString())
            ->where("is_active", "=", 1)->whereIn("id", $bookmarkIds)->get();

        return response(JobResource::collection($jobs));
    }


}
