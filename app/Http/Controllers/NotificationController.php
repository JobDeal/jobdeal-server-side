<?php

namespace App\Http\Controllers;

use App\Http\Helper;
use App\Http\NotificationConst;
use App\Http\Resources\NotificationResource;
use App\Jobs\PushNotification;
use App\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    private const NOTIFICATION_TYPE_DOER = 0;
    private const NOTIFICATION_TYPE_BUYER = 1;

    /**
     * @SWG\Get(
     *     path="/notification/get/{id}",
     *     summary="Get a notification",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="path",
     *         name="id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function getNotificationById($id){
        $notification = Notification::where("id", "=", $id)->first();

        if(!$notification)
            return response(Helper::jsonError("Notification not found."), 404);

        return response(new NotificationResource($notification));
    }

    /**
     * @SWG\Get(
     *     path="/notification/app/{page}",
     *     summary="Get all notification",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="path",
     *         name="page",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function getAllNotifications($page = 0){
        $notifications = Notification::where("user_id", "=", Auth::user()->id)->limit(20)->offset($page * 20)->orderBy("id", "DESC")->get();

        //Notification::where("user_id", "=", Auth::user()->id)->limit(20)->offset($page * 20)->update(['shown' => 1]);

        return response(NotificationResource::collection($notifications));
    }

    /**
     * @SWG\Get(
     *     path="/notification/read/{id}",
     *     summary="Read notification",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="path",
     *         name="id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function readNotification($id){
        $notification = Notification::where('id', $id)->first();

        if($notification){
            $notification->shown = 1;
            $notification->save();
        }

        return response("{}", 200, ["Content-Type" => "application/json"]);
    }

    /**
     * @SWG\Get(
     *     path="/notification/types/{type}/{page}",
     *     summary="Get Separated Notifications",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="path",
     *         name="type",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         in="path",
     *         name="page",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function getSeparatedNotifications($type, $page = 0){

        switch ($type) {
            case self::NOTIFICATION_TYPE_DOER:
                $notifications = $this->getDoerNotifications($this->getValidTypes($type) , $page);
                break;
            case self::NOTIFICATION_TYPE_BUYER:
                $notifications = $this->getBuyerNotifications($this->getValidTypes($type), $page);
                break;

            default:
                break;
        }

        return response(NotificationResource::collection($notifications));
    }

    /**
     * @SWG\Delete(
     *     path="/notification/delete/{id}",
     *     summary="Delete a notification",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="path",
     *         name="id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function deleteNotification($id){
        $notification = Notification::where("id", "=", $id)->first();

        if(!$notification)
            return response("Notification not found!", 404);

        if ($notification->user_id != Auth::user()->id)
            return response("Delete not allow!", 400);

        $res = $notification;
        $notification->delete();

        return response(new NotificationResource($res));
    }

    /**
     * @SWG\Get(
     *     path="/notification/unread",
     *     summary="Get unread count",
     *     security={{"bearer_token":{}}},
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function getUnreadCount(){
        $notificationDoerCount = Notification::where('user_id', '=', Auth::user()->id)
            ->whereIn('type', [NotificationConst::BuyerAccepted, NotificationConst::RateBuyer, NotificationConst::WishlistJob])
            ->where('shown', 0)->count();
        $notificationBuyerCount = Notification::where('user_id', '=', Auth::user()->id)
            ->whereIn('type', [NotificationConst::DoerBid, NotificationConst::RateDoer, NotificationConst::PaymentSuccess, NotificationConst::PaymentError])
            ->where('shown', 0)->count();

        $res = array();
        $res['unreadCount'] = $notificationBuyerCount + $notificationDoerCount;
        $res['unreadDoerCount'] = $notificationDoerCount;
        $res['unreadBuyerCount'] = $notificationBuyerCount;

        return response($res);
    }

    public function sendTestNotification() {
        $userId = 112;

        $notification = Notification::where('user_id', '=', $userId)->orderBy('created_at', 'desc')->first();
        $newNotification = new Notification();
        $newNotification->user_id = $notification->user_id;
        $newNotification->from_id = $notification->from_id;
        $newNotification->job_id = $notification->job_id;
        $newNotification->type = $notification->type;
        $newNotification->save();

        PushNotification::dispatch($userId, -1, NotificationConst::DoerBid, $notification->id, [], __("lang.paymentError_description"),
            __("lang.paymentError_title"), $sendNotification = true);

    }

    /**
     * @param $type
     * @return array
     */
    public function getValidTypes($type): array
    {
        $types = [];
        if ($type == self::NOTIFICATION_TYPE_DOER) { //doer
            $types = [NotificationConst::BuyerAccepted, NotificationConst::RateBuyer, NotificationConst::WishlistJob];
        } else if ($type == self::NOTIFICATION_TYPE_BUYER) { //buyer
            $types = [NotificationConst::DoerBid, NotificationConst::RateDoer, NotificationConst::PaymentSuccess, NotificationConst::PaymentError];
        }
        return $types;
    }

    private const DOER_QUERY = <<< DOER
SELECT * FROM (
    SELECT n.* FROM notifications as n
    JOIN jobs j
    JOIN applicants a
    WHERE n.user_id = :userId
    AND n.job_id = j.id
    AND a.user_id = n.user_id
    AND a.job_id = n.job_id
    AND n.type IN (%s)
    AND (
        (j.expire_at > :now AND (a.choosed_at IS NULL OR (a.choosed_at IS NOT NULL AND n.type != :typeWishlist)))
        OR
        (j.expire_at < :now2 AND (n.type = :typeRateBuyer))
    )
    UNION
    SELECT n2.* FROM notifications n2
    LEFT JOIN jobs j2 ON n2.job_id = j2.id
    WHERE n2.user_id = :userId2
    AND n2.type = :typeWishlist2
    AND j2.expire_at > :now3
    AND n2.job_id NOT IN (
        SELECT DISTINCT a2.job_id FROM applicants a2
            JOIN jobs j2 ON a2.job_id = j2.id
            WHERE a2.user_id = :userId3
    )
) as tmp
ORDER BY tmp.id DESC
LIMIT 20
OFFSET %d
DOER;

    /**
     * @param array $types
     */
    public function getDoerNotifications(array $types, $page)
    {
        $userId = Auth::user()->id;
        $now = new \DateTime();
        $notificationData = Db::select(
            sprintf(self::DOER_QUERY,  implode(",", $types), $page * 20),
            [
                ":userId" => $userId,
                ":userId2" => $userId,
                ":userId3" => $userId,
                ":now" => $now,
                ":now2" => $now,
                ":now3" => $now,
                ":typeWishlist" => NotificationConst::WishlistJob,
                ":typeWishlist2" => NotificationConst::WishlistJob,
                ":typeRateBuyer" => NotificationConst::RateBuyer
            ]
        );

        return  Notification::hydrate($notificationData);
    }

    private const BUYER_QUERY = <<< BUYER
SELECT n.* FROM notifications n
    LEFT JOIN jobs j on n.job_id = j.id
    WHERE n.user_id = :userId
    AND n.type IN (%s)
    AND NOT (j.expire_at > :currentTime AND n.type = :excludeType)
    ORDER BY n.id DESC
    LIMIT 20
    OFFSET %d
BUYER;

    /**
     * @param array $types
     * @param $page
     * @return mixed
     */
    public function getBuyerNotifications(array $types, int $page)
    {
        $userId = Auth::user()->id;
        $now = new \DateTimeImmutable();
        $notificationData = Db::select(
            sprintf(self::BUYER_QUERY, implode(",", $types), $page * 20),
            [
                ":userId" => $userId,
                ":currentTime" => $now,
                ":excludeType" => NotificationConst::DoerBid,
            ]
        );

        return Notification::hydrate($notificationData);
    }
}
