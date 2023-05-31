<?php

namespace App\Http\Controllers;

use App\Applicant;
use App\Bookmark;
use App\Http\Helper;
use App\Http\JobConst;
use App\Http\NotificationConst;
use App\Http\Resources\ApplicantResource;
use App\Http\Resources\JobLiteResource;
use App\Http\Resources\JobPushResource;
use App\Http\Resources\JobResource;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\ReportResource;
use App\Http\Resources\UserResource;
use App\Job;
use App\JobImage;
use App\Jobs\CheckWishlist;
use App\Jobs\PushNotification;
use App\Notification;
use App\Report;
use App\User;
use Carbon\Carbon;
use MatanYadaev\EloquentSpatial\Objects\Point;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

class JobController extends Controller
{
    /**
     * @OA\Post(
     *     path="/job/add",
     *     summary="Add a job",
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string", description="Job Name"),
     *                 @OA\Property(property="description", type="string", description="Job description"),
     *                 @OA\Property(property="price", type="integer", description="Job price"),
     *                 @OA\Property(property="address", type="string", description="Job address"),
     *                 @OA\Property(property="categoryId", type="integer", description="Job category"),
     *                 @OA\Property(property="isBoost", type="boolean"),
     *                 @OA\Property(property="isDelivery", type="boolean"),
     *                 @OA\Property(property="latitude", type="string"),
     *                 @OA\Property(property="longitude", type="string"),
     *                 @OA\Property(property="expireAt", type="string",),
     *                 @OA\Property(
     *                     property="images",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="path", type="string"),
     *                         @OA\Property(property="position", type="integer")
     *                     )
     *                 )
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function addJob(Request $request)
    {
        $validator = Validator::make($request->json()->all(), [
            //'name' => 'required'
        ]);

        if ($validator->fails()) {
            return response($validator->messages()->first(), 400);
        }

        if(Carbon::now()->greaterThan(Carbon::parse($request->json("expireAt")))){
            return response(Helper::jsonError(__("lang.expired_at_error", [], Auth::user()->locale)),470);
        }

        $job = new Job();
        $job->user_id = Auth::user()->id;
        $job->name = $request->json('name');
        $job->description = $request->json('description');
        $job->price = $request->json('price');
        $job->address = $request->json('address');
        $job->category_id = $request->json('categoryId');
        $job->is_boost = $request->json('isBoost', 0);
        $job->is_delivery = $request->json('isDelivery', 0);
        $job->country = Auth::user()->country;
        $job->status = 1;
        //$job->location_string = $request->json("latitude")." ".$request->json("longitude");
        $job->location = new Point($request->json('latitude'), $request->json('longitude'));
        if (Auth::user()->timezone) {
            $job->expire_at = Carbon::parse($request->json("expireAt"))->toDateTimeString();
            $job->expire_at = Carbon::createFromFormat('Y-m-d H:i:s', $job->expire_at, Auth::user()->timezone)->setTimezone('UTC');
        } else {
            $job->expire_at = Carbon::parse($request->json("expireAt"))->toDateTimeString();

        }


        //TODO Treba napraviti za promo code

        $job->save();

        foreach ($request->json("images") as $element) {
            $job = Job::where("id", "=", $job->id)->first();

            if (!$job)
                return response(Helper::jsonError("Job not found."), 404);

            try {
                $jobImage = new JobImage();
                $jobImage->path = $element['path'];
                $jobImage->position = $element['position'];
                $jobImage->job_id = $job->id;
                $jobImage->save();
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        }

        //check wishlist
        CheckWishlist::dispatch($job);

        return response(new JobResource($job));
    }

    /**
     * @OA\Put(
     *     path="/job/edit",
     *     summary="Update a job",
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(property="id", type="integer", description="Job ID"),
     *                 @OA\Property(property="name", type="string", description="Job Name"),
     *                 @OA\Property(property="description", type="string", description="Job description"),
     *                 @OA\Property(property="price", type="integer", description="Job price"),
     *                 @OA\Property(property="address", type="string", description="Job address"),
     *                 @OA\Property(property="categoryId", type="integer", description="Job category"),
     *                 @OA\Property(property="isBoost", type="boolean"),
     *                 @OA\Property(property="isSpeedy", type="boolean"),
     *                 @OA\Property(property="isDelivery", type="boolean"),
     *                 @OA\Property(property="latitude", type="string"),
     *                 @OA\Property(property="longitude", type="string"),
     *                 @OA\Property(property="expireAt", type="string",),
     *                 @OA\Property(
     *                     property="images",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="path", type="string"),
     *                         @OA\Property(property="position", type="integer")
     *                     )
     *                 )
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function editJob(Request $request)
    {
        $validator = Validator::make($request->json()->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return response($validator->messages()->first(), 400);
        }

        $job = Job::where("id", "=", $request->json('id'))->first();


        if ($job) {

            if (key_exists('name', $request->all())) {
                $job->name = $request->json('name');
            }

            if (key_exists('description', $request->all())) {
                $job->description = $request->json('description');
            }

            if (key_exists('categoryId', $request->all())) {
                $job->category_id = $request->json('categoryId');
            }

            if (key_exists('price', $request->all())) {
                $job->price = $request->json('price');
            }

            if (key_exists('expireAt', $request->all())) {
                $job->expire_at = $request->json('expireAt');
            }

            if (key_exists('address', $request->all())) {
                $job->address = $request->json('address');
            }

            if (key_exists('isBoost', $request->all())) {
                $job->is_boost = $request->json('isBoost');
            }

            if (key_exists('isSpeedy', $request->all())) {
                $job->is_speedy = $request->json('isSpeedy');
            }

            if (key_exists('isDelivery', $request->all())) {
                $job->is_delivery = $request->json('isDelivery');
            }

            if (key_exists('longitude', $request->all()) && key_exists('latitude', $request->all())) {
                $job->location_string = $request->json("latitude") . " " . $request->json("longitude");
                $job->location = new Point($request->json('latitude'), $request->json('longitude'));
            }


            $job->save();

            foreach ($request->json("images") as $element) {

                try {
                    $jobImage = new JobImage();
                    $jobImage->path = $element['path'];
                    $jobImage->position = $element['position'];
                    $jobImage->job_id = $job->id;
                    $jobImage->save();
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                }
            }

            return response(new JobResource($job));

        } else {

            return response(Helper::jsonError("Job not found."), 404);

        }

    }

    /**
     * @OA\Delete(
     *     path="/job/delete",
     *     summary="Delete a job",
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(property="id", type="integer", description="Job ID")
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function deleteJob(Request $request)
    {
        Job::where("id", "=", $request->json("id"))->delete();
        Notification::where("job_id", "=", $request->json("id"))->delete();
        Applicant::where("job_id", "=", $request->json("id"))->delete();
        Bookmark::where("job_id", "=", $request->json("id"))->delete();
        JobImage::where("job_id", "=", $request->json("id"))->delete();

        return response("{}");
    }

    /**
     * @OA\Post(
     *     path="/job/report",
     *     summary="Add report to a job",
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(property="reportText", type="string"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer")
     *                 ),
     *                 @OA\Property(
     *                     property="job",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer")
     *                 )
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function addReport(Request $request)
    {
        $exists = Report::where("user_id", "=", $request->json("user")["id"])
            ->where("job_id", "=", $request->json("job")["id"])
            ->first();

        if ($exists)
            return response(new ReportResource($exists));

        $report = new Report();
        $report->user_id = Auth::user()->id;
        $report->job_id = $request->json("job")["id"];
        $report->report_text = $request->json("reportText", "");
        $report->save();

        return response(new ReportResource($report));

    }

    /**
     * @OA\Parameter(
     *    @OA\Schema(type="integer"),
     *    in="path",
     *    allowReserved=true,
     *    name="id",
     *    parameter="job_id"
     * )
     * @OA\Get(
     *     path="/job/get/{id}",
     *     summary="Get a job",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         ref="#/components/parameters/job_id"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function getJobById($id)
    {
        $job = Job::where("id", "=", $id)->first();

        if (!$job)
            return response(Helper::jsonError("Job not found."), 404);

        return response(new JobResource($job));
    }

    /**
     * @OA\Get(
     *     path="/job/recent",
     *     summary="Get recent jobs",
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function getRecentJobs()
    {
        $jobs = Job::where("expire_at", ">=", Carbon::now()->toDateTimeString())
            ->where("is_active", "=", 1)->orderBy('created_at', "DESC")->limit(4)->get();

        return response(JobResource::collection($jobs));
    }

    /**
     * @OA\Parameter(
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="type",
     *     required=true,
     *     parameter="type"
     * )
     * @OA\Parameter(
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="page",
     *     required=true,
     *     parameter="page"
     * )
     * @OA\Post(
     *     path="/job/filter/{type}/{page}",
     *     summary="Filter jobs",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         ref="#/components/parameters/type"
     *     ),
     *     @OA\Parameter(
     *         ref="#/components/parameters/page"
     *     ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(property="filter", type="string"),
     *                 @OA\Property(property="sortBy", type="string"),
     *                 @OA\Property(property="sortDirection", type="string"),
     *                 @OA\Property(
     *                     property="currentLocation",
     *                     type="object",
     *                     @OA\Property(property="lat", type="string"),
     *                     @OA\Property(property="lng", type="string")
     *                 )
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function filterJobs(Request $request, $type = 0, $page = 0)
    {
        $sortColumns = ["published" => "created_at", "price" => "price", "expiration" => "expire_at"];

        $filter = $request->json("filter");
        $sortBy = $request->json("sortBy");
        $sortDirection = $request->json("sortDirection");

        //if no currentLocation in filter, get location from IP address
        if (!$request->has("currentLocation") || $request->json("currentLocation")["lat"] == 0) {
            if (Auth::user()) {
                $user = User::where('id', '=', Auth::user()->id)->first();
            } else {
                $user = null;
            }


            if ($user && $user->lat != null && $user->lng != null) {


                $loc = [];
                $loc["currentLocation"]["lat"] = $user->lat;
                $loc["currentLocation"]["lng"] = $user->lng;

                $request->merge($loc);


            } else {


                $client = new Client([]);
                $resIP = $client->request('GET', 'http://ip-api.com/json/' . \request()->ip(), []);

                if ($resIP->getStatusCode() == 200) {
                    $lat = json_decode($resIP->getBody(), true)["lat"];
                    $lng = json_decode($resIP->getBody(), true)["lon"];

                    $loc = [];
                    $loc["currentLocation"]["lat"] = $lat;
                    $loc["currentLocation"]["lng"] = $lng;

                    $request->merge($loc);
                } else {
                    Log::error("Country fail!");
                    return response("Country fail", 404);
                }
            }
        }

        if (!key_exists("location", $request->json("filter"))) {//for list-grid
            if ($type == 0) {
                //Log::debug(Job::filter($filter)->orderByRaw("is_boost DESC , $sortColumns[$sortBy] $sortDirection")->limit(10)->offset($page * 10)->toSql());
                $jobs = Job::filter($filter)->where("is_offensive", "=", 0)->orderByRaw("is_boost DESC , $sortColumns[$sortBy] $sortDirection")->limit(10)->offset($page * 10)->get();
                $jobsCount = Job::filter($filter)->where("is_offensive", "=", 0)->count();
            } else if ($type == 1) {
                $jobs = Job::filter($filter)->where("is_speedy", "=", 1)->where("is_delivery", "=", 0)->where("is_offensive", "=", 0)->orderByRaw("is_boost DESC , $sortColumns[$sortBy] $sortDirection")->limit(10)->offset($page * 10)->get();
                $jobsCount = Job::filter($filter)->where("is_speedy", "=", 1)->where("is_offensive", "=", 0)->count();
            } else if ($type == 2) {
                $jobs = Job::filter($filter)->where("is_speedy", "=", 0)->where("is_delivery", "=", 1)->where("is_offensive", "=", 0)->orderByRaw("is_boost DESC , $sortColumns[$sortBy] $sortDirection")->limit(10)->offset($page * 10)->get();
                $jobsCount = Job::filter($filter)->where("is_speedy", "=", 0)->where("is_delivery", "=", 1)->where("is_offensive", "=", 0)->count();
            }
        } else {//for map
            if ($type == 0) {
                $jobs = Job::filter($filter)->where("is_offensive", "=", 0)->limit(500)->get();
                $jobsCount = Job::filter($filter)->where("is_offensive", "=", 0)->count();
            } else if ($type == 1) {
                $jobs = Job::filter($filter)->where("is_speedy", "=", 1)->where("is_delivery", "=", 0)->where("is_offensive", "=", 0)->limit(500)->get();
                $jobsCount = Job::filter($filter)->where("is_speedy", "=", 1)->where("is_delivery", "=", 0)->where("is_offensive", "=", 0)->count();
            } else if ($type == 2) {
                $jobs = Job::filter($filter)->where("is_speedy", "=", 0)->where("is_delivery", "=", 1)->where("is_offensive", "=", 0)->limit(500)->get();
                $jobsCount = Job::filter($filter)->where("is_speedy", "=", 0)->where("is_delivery", "=", 1)->where("is_offensive", "=", 0)->count();
            }
        }


        $res["total"] = $jobsCount;
        $res["jobs"] = JobResource::collection($jobs);

        return response($res);
    }

    /**
     * @OA\Post(
     *     path="/job/apply",
     *     summary="Apply to a job",
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(property="price", type="integer"),
     *                 @OA\Property(
     *                     property="job",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer")
     *                 )
     *             )
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function applyToJob(Request $request)
    {

        $job = Job::where("id", "=", $request->json('job')["id"])->first();
        $user = User::where("id", "=", Auth::user()->id)->first();

        if (!$job)
            return response(Helper::jsonError("Job not found!"), 404);

        $exists = Applicant::where("user_id", "=", Auth::user()->id)->where("job_id", "=", $job->id)->first();

        if ($exists)
            return response(new ApplicantResource($exists));

        $applicant = new Applicant();
        $applicant->user_id = Auth::user()->id;
        $applicant->job_id = $request->json('job')["id"];
        $applicant->price = $request->json('price');
        $applicant->save();

        //add notification do user that doer bid
        $notification = new Notification();
        $notification->user_id = $job->user->id;
        $notification->from_id = Auth::user()->id;
        $notification->job_id = $job->id;
        $notification->type = NotificationConst::DoerBid;
        $notification->save();

        $job = Job::where("id", "=", $request->json('job')["id"])->first();

        Log::debug("Apply to job: " . json_encode(new JobLiteResource($job)));

        PushNotification::dispatch($job->user_id, $user->id, NotificationConst::DoerBid, $notification->id,
            new JobPushResource($job), __("lang.doerBid_description", [], Auth::user()->locale), __("lang.doerBid_title", [], Auth::user()->locale),
            $sendNotification = true);


        return response(new JobResource($job));
    }

    /**
     * @OA\Parameter(
     *    @OA\Schema(type="string"),
     *    in="path",
     *    allowReserved=true,
     *    name="jobId",
     *    parameter="jobId"
     * )
     * @OA\Get(
     *     path="/job/applicants/{jobId}",
     *     summary="Get the applicants of the job",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         ref="#/components/parameters/jobId"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function getJobApplicants($id)
    {
        $job = Job::where("id", "=", $id)->first();

        /*if($job->user_id != Auth::user()->id)
            return response(Helper::jsonError("Only owner of job can get applicants!"), 400);*/

        $job->status = JobConst::StatusSeeApplicants;
        $job->save();

        $applicants = Applicant::where("job_id", "=", $id)->get();

        return response(ApplicantResource::collection($applicants));
    }

    /**
     * @OA\Post(
     *     path="/job/applicants/choose/{jobId}",
     *     summary="Choose applicant",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         ref="#/components/parameters/jobId"
     *     ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(property="id", type="integer", description="User ID"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                    @OA\Property(property="id", type="integer", description="User ID")
     *                ),
     *                @OA\Property(
     *                     property="helpOnTheWay",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer")
     *                )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function chooseJobApplicant(Request $request, $jobId)
    {
        $job = Job::where("id", "=", $jobId)->first();

        if (!$job)
            return response(Helper::jsonError("Job not found!"), 404);

        //check if applicat already applyed
        $alreadyExists = $job->applicants()->where("choosed_at", "!=", null)->where("user_id", "=", $request->json("id"))->first();

        if ($alreadyExists)
            return response(Helper::jsonError("Doer already selected!"), 400);

        //check max applicant needed
        $choosedCount = $job->applicants()->where("choosed_at", "!=", null)->count();
        if ($choosedCount >= $job->applicant_count) { //if max applicatn seleced change status to
            $job->status = JobConst::StatusDoerChoosed;
            $job->save();
        }

        $applicant = Applicant::where("job_id", "=", $jobId)->where("user_id", "=", $request->json("user")["id"])->first();

        if (!$applicant)
            return response(Helper::jsonError("Doer not found!"), 400);

        $applicant->choosed_at = Carbon::now()->toDateTimeString();
        $applicant->save();

        //add notification to doer that buyer accepted bid
        $notification = new Notification();
        $notification->user_id = $request->json("user")["id"];
        $notification->from_id = Auth::user()->id;
        $notification->job_id = $job->id;
        $notification->type = NotificationConst::BuyerAccepted;
        $notification->save();

        if (key_exists('helpOnTheWay', $request->json('job'))) {

            if ($request->json('job')['helpOnTheWay'] == true) {
                $job->help_on_the_way = Carbon::now()->toDateString();
                $job->save();
            } else {
                $job->help_on_the_way = null;
                $job->save();
            }

        }

        PushNotification::dispatch($applicant->user_id, $job->user->id, NotificationConst::BuyerAccepted, $notification->id, new JobLiteResource($job), __("lang.doerGotJob_description", [], Auth::user()->locale),
            __("lang.doerGotJob_title", [], Auth::user()->locale), $sendNotification = true);

        return response(new JobResource($job));
    }

    /**
     * @OA\Parameter(
     *    @OA\Schema(type="integer"),
     *    in="path",
     *    allowReserved=true,
     *    name="userId",
     *    parameter="userId"
     * )
     * @OA\Get(
     *     path="/job/buyer/getAll/{userId}/{page}",
     *     summary="Get buyer jobs",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         ref="#/components/parameters/userId"
     *     ),
     *     @OA\Parameter(
     *         ref="#/components/parameters/page"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function getBuyerJobs($userId, $page = 0)
    {

        $userJobs = Job::where("user_id", "=", $userId)
            ->where('expire_at', ">", Carbon::now()->toDateTimeString())
            ->orderBy("created_at", "DESC")->limit(20)->offset(20 * $page)->get();

        return response(JobResource::collection($userJobs));
    }

    /**
     * @OA\Get(
     *     path="/job/doer/getAll/{page}",
     *     summary="Get doer jobs",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         ref="#/components/parameters/page"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function getDoerJobs($page = 0)// deprecated
    {

        $jobIds = Applicant::where("user_id", "=", Auth::user()->id)->orderBy("created_at", "DESC")->limit(20)->offset(20 * $page)->get()->pluck("job_id");
        $jobs = Job::whereIn("id", $jobIds)->orderBy("created_at", "DESC")->get();

        return response(JobResource::collection($jobs));
    }

    /**
     * @OA\Get(
     *     path="job/doer/v2/getAll/{type}/{page}",
     *     summary="Get doer jobs",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         ref="#/components/parameters/type"
     *     ),
     *     @OA\Parameter(
     *         ref="#/components/parameters/page"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function getDoerJobsV2($type = 1, $page = 0)
    { // get jobs where user/doer is appyed

        $jobIds = Applicant::where("user_id", "=", Auth::user()->id)->orderBy("created_at", "DESC")->limit(20)->offset(20 * $page)->get()->pluck("job_id");

        if ($type == 1) { // active jobs

            $jobs = Job::whereIn("id", $jobIds)->where('expire_at', '>', Carbon::now()->toDateTimeString())->orderBy("created_at", "DESC")->get();

        } else if ($type == 2) { // expired jobs

            $jobs = Job::whereIn("id", $jobIds)->where('expire_at', '<=', Carbon::now()->toDateTimeString())->orderBy("created_at", "DESC")->get();

        }


        return response(JobResource::collection($jobs));

    }

    public function uploadImage(Request $request)
    {

        //Log::info("uploadImage!");

         $validator = Validator::make($request->all(), [
             'image' => 'required',
             //'image_files.*' => 'image|max:51200'
         ]);

         if ($validator->fails()) {
             return response($validator->errors()->toJson(), 400);
         }

        $response = [];

        //Log::debug("Upload: " . json_encode($request->all()));
        try {
            if ($request->hasFile('image')) {

                $file = $request->file("image");

                $path = $file->store('jobs', ['disk' => 's3']);

                $response[] = [
                    "fullUrl" => Storage::disk("s3")->url($path),
                    "path" => Storage::disk("s3")->path($path)
                ];
            }
        } catch (\Exception $e) {
            Log::error($file->getErrorMessage());
        }

        return response($response);
    }

    public function addJobImage(Request $request)
    {

        foreach ($request->json()->all() as $element) {

            $job = Job::where("id", "=", $element['jobId'])->first();

            if (!$job)
                return response(Helper::jsonError("Job not found."), 404);

            $db = DB::delete("delete from media where model_id = ? and order_column = ?", [$element['jobId'], $element['position']]);


            $media = $job->addMedia(Storage::path($element['path']))
                ->toMediaCollection("jobs");

            $media->order_column = $element['position'];
            $media->save();

        }

        return response(new JobResource($job));
    }

    public function removeJobImage($jobId, $position)
    {
        $job = Job::where("id", "=", $jobId)->first();

        if (!$job)
            return response(Helper::jsonError("Job not found."), 404);

        $db = DB::delete("delete from media where model_id = ? and order_column = ?", [$jobId, $position]);

        return response(new JobResource($job));
    }
}
