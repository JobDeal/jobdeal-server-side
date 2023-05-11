<?php

namespace App\Http\Controllers;



use App\Conversation;
use App\Http\Helper;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Message;
use App\Participant;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ConversationController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/conversation/user/unread",
     *     summary="Get unread conversations",
     *     security={{"bearer_token":{}}},
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function getUnreadConversation(){
        $unread = Participant::where("user_id", Auth::user()->id)
            ->where("unread", ">", 0)
            ->orderBy("created_at", "DESC")->sum('unread');

        return $unread;
    }

    /**
     * @SWG\Get(
     *     path="/conversation/all/{page}",
     *     summary="Get conversations",
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
    public function getConversation($page = 0){
        $userId = Auth::user()->id;

        $conversations = Conversation::whereHas('participants', function($q) use ($userId)
        {
            $q->where('user_id','=',$userId);

        })->orderBy("updated_at", "DESC")->limit(20)->offset($page * 20)->get();

        return response(ConversationResource::collection($conversations));
    }

    /**
     * @SWG\Get(
     *     path="/conversation/message/{conversationId}/{page}",
     *     summary="Get messages by conversation Id",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="path",
     *         name="conversationId",
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
    public function showConversation($conversationId, $page = 0){
        $user = User::find(Auth::user()->id);

        $messages = Message::where("conversation_id", $conversationId)->limit(20)->offset($page * 20)->orderBy("created_at", "DESC")->get();

        foreach ($messages as $message)
        {
            $messagesSeen = [ $message->id => [ 'seen_at' => Carbon::now() ]];
            $user->messages()->sync($messagesSeen, false);
        }

        $participant = Participant::where("conversation_id", $conversationId)
            ->where("user_id", Auth::user()->id)
            ->first();

        $participant->unread = 0;
        $participant->save();

        return response(MessageResource::collection($messages));
    }


    /**
     * @SWG\Post(
     *     path="/conversation/message/add",
     *     summary="Add message to the conversation",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="conversationId", type="integer"),
     *             @SWG\Property(property="conversationName", type="string"),
     *             @SWG\Property(property="body", type="string")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function addMessage(Request $request){
        $validator = Validator::make($request->json()->all(), [
            //'receiverId' => 'required'
        ]);

        if ($validator->fails()) {
            return response($validator->messages()->first(), 400);
        }

        if (!$request->json('conversationId')) {

            $participients = $request->json('participients');

            $conversation = new Conversation();
            $conversation->name = $request->json('conversationName') ? $request->json('conversationName') : 'no name';
            $conversation->latest_message = $request->json('body');
            $conversation->sender_id = Auth::user()->id;
            $conversation->save();

            $participient = new Participant();
            $participient->user_id = Auth::user()->id;
            $participient->conversation_id = $conversation->id;
            $participient->save();

            foreach ($participients as $part)
            {
                $participient = new Participant();
                $participient->user_id = $part["id"];
                $participient->conversation_id = $conversation->id;
                $participient->save();
            }

            $message = new Message();
            $message->sender_id = Auth::user()->id;
            $message->conversation_id = $conversation->id;
            $message->body = $request->json('body');
            $message->save();

        } else {
            $conversation = Conversation::find($request->json('conversationId'));
            $conversation->sender_id = Auth::user()->id;
            $conversation->latest_message = $request->json('body');
            $conversation->save();

            $message = new Message();
            $message->sender_id = Auth::user()->id;
            $message->conversation_id = $request->json('conversationId');
            $message->body = $request->json('body');
            $message->save();
        }
        foreach ($message->conversation->participants as $participant) {

            if($participant->user_id == Auth::user()->id) {
                $participient = Participant::find($participant->id);
                $participient->unread = 0;
                $participient->save();
            } else {
                $participient = Participant::find($participant->id);
                $participient->unread += 1;
                $participient->save();
            }

            //OVO BI PO SPECIFIKACIJI TREBALO DA BUDE, ALI JE BAS KRETENSKI, DOBIJAS NOTIFIKACIJU U LISTI ZA SVAKU PORUKU STO JE DEBILIZAM
            /*  $notification = new Notification();
              $notification->message_id = $message->id;
              $notification->from_id = Auth::user()->id;
              $notification->user_id = $participant->user_id;
              $notification->type = NotificationConst::Message;
              $notification->save();



              SendPushToUser::dispatch($participant->user_id, Auth::user()->id, "new_message", $notification->id, Auth::user()->name,
                  __("lang.new_message", [], $participant->user->locale),
                  Auth::user()->name);*/

//            SendPushToUser::dispatch($participant->user_id, Auth::user()->id, "message", $message->conversation->id . "|" . $message->id, Auth::user()->name,
//                mb_strimwidth($message->body,0, 300, "..."),
//                Auth::user()->name
//           );
        }

        return response(new MessageResource($message));
    }

    /**
     * @SWG\Get(
     *     path="/conversation/message/{id}",
     *     summary="Get message by Id",
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
    public function getMessageById($id){
        $message = Message::where("id", "=", $id)->first();

        if(!$message)
            return response(Helper::jsonError("Message not found."), 404);

        //set all messages as read
        $participant = Participant::where("conversation_id", $message->conversation_id)
            ->where("user_id", Auth::user()->id)
            ->first();

        $participant->unread = 0;
        $participant->save();

        return response(new MessageResource($message));
    }

    /**
     * @SWG\Post(
     *     path="/conversation/message/get",
     *     summary="Get message by Ids",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 type="integer"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function getMessagesByIds(Request $request){
        $messages = Message::whereIn("id", $request->all())->orderBy("created_at", "DESC")->get();


        //set all messages as read
        foreach ($messages as $message) {
            $participant = Participant::where("conversation_id", $message->conversation_id)
                ->where("user_id", Auth::user()->id)
                ->first();

            $participant->unread = 0;
            $participant->save();
        }

        return response(MessageResource::collection($messages));
    }

    /**
     * @SWG\Get(
     *     path="/conversation/get/{id}",
     *     summary="Get conversation by Id",
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
    public function getConversationById($id){
        $conversation = Conversation::where("id", "=", $id)->first();

        return response(new ConversationResource($conversation));
    }
}
