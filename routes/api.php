<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::get('testInvoice', 'Controller@testInvoice');

Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
Route::post('test/notification/{userId}', 'Controller@testNotification');
//USER
Route::post('panel/admin/login', 'AdminController@login');

Route::post('user/register', 'UserController@register');
Route::post('user/login', 'UserController@login');
Route::post('user/image/upload', 'UserController@uploadImage');
Route::post('user/password/forgot', 'UserController@forgotPassword');
Route::post('user/resetpassword', 'UserController@resetPassword');
Route::post('user/email/check', 'UserController@checkEmail');
//JOB
Route::post('job/image/upload', 'JobController@uploadImage');
Route::get('job/recent', 'JobController@getRecentJobs');

//BANKID
Route::post('bankid/auth', 'UserController@bankIdAuth');
Route::post('bankid/collect', 'UserController@bankIdCollect');
//PAYMENT - SWISH
Route::post('payment/swish/callback', 'PaymentController@swishCallback');
//PAYMENT - KLARNA
Route::post('payment/klarna/push/{order_id}', 'PaymentController@klarnaPushEvent');
Route::post('payment/klarna/checkout/{order_id}', 'PaymentController@klarnaCheckoutEvent');
Route::get('payment/klarna/confirmation/{order_id}', 'PaymentController@klarnaConfirmationEvent');
Route::post('payment/klarna/validation', 'PaymentController@klarnaValidateEvent');



//SMS Verification

Route::post('verification/send', 'UserController@requestVerification');
Route::post('verification/verify', 'UserController@verifyCode');

Route::middleware(['jwtMiddlePass'])->group(function () {
    //JOB
    Route::post('job/filter/{type}/{page}', 'JobController@filterJobs');
    //Route::post('job/image/add', 'JobController@addJobImage');
    //Route::get('job/image/delete/{jobId}/{position}', 'JobController@removeJobImage');
    Route::get('job/get/{jobId}', 'JobController@getJobById');


    //CATEGORY
    Route::post('category/add', 'CategoryController@addCategory');
    Route::get('category/get/{id}', 'CategoryController@getCategoryById');
    Route::get('category/all/{root_id}', 'CategoryController@getAllCategories');
    Route::delete('category/delete/{id}', 'CategoryController@deleteCategory');

    //DIRECTIONS
    Route::get('directions/address/{lat}/{lng}', 'DirectionsController@getAddressFromLocation');
    Route::get('directions/location/{address}', 'DirectionsController@getLocationFromAddress');
});

Route::middleware(['jwtMiddle'])->group(function () {
    Route::get('user/logout', 'UserController@logout');
    Route::post('user/delete', 'UserController@deleteAccount');

    //DEVICE
    Route::post('user/device/add', 'DevicesController@addDevice');

    //USER
    Route::post('user/logout', 'UserController@logout');
    Route::get('user/get/{userId}', 'UserController@getUserById');
    Route::post('user/update', 'UserController@updateUser');
    Route::post('user/password', 'UserController@changePassword');
    Route::get('user/verify/{token}', 'UserController@verifyEmail');
    Route::post('user/location/update', 'UserController@saveLocation');

    //RATES
    Route::post('rate/add', 'RateController@addRate');
    Route::get('rate/byBuyerId/{userId}/{page}', 'RateController@getRateByBuyerId');
    Route::get('rate/byDoerId/{userId}/{page}', 'RateController@getRateByDoerId');

    //JOB
    Route::post('job/add', 'JobController@addJob');
    Route::put('job/edit', 'JobController@editJob');
    Route::post('job/apply', 'JobController@applyToJob');
    Route::get('job/applicants/{jobId}', 'JobController@getJobApplicants'); // get doers by job id
    Route::get('job/doer/getAll/{page}', 'JobController@getDoerJobs');//deprecated
    Route::get('job/doer/v2/getAll/{type}/{page}', 'JobController@getDoerJobsV2');//get doer jobs
    Route::get('job/buyer/getAll/{userId}/{page}', 'JobController@getBuyerJobs'); // get buyer jobs
    Route::post('job/applicants/choose/{jobId}', 'JobController@chooseJobApplicant');// buyer choose doer for job
    Route::delete('job/delete', 'JobController@deleteJob');
    Route::post('job/report', 'JobController@addReport');

    //CONVERSATION
    Route::post('conversation/message/add', 'ConversationController@addMessage');
    Route::get('conversation/all/{page}', 'ConversationController@getConversation');
    Route::get('conversation/message/{conversationId}/{page}', 'ConversationController@showConversation');
    Route::get('conversation/message/{id}', 'ConversationController@getMessageById');
    Route::post('conversation/message/get', 'ConversationController@getMessagesByIds');
    Route::get('conversation/get/{id}', 'ConversationController@getConversationById');
    Route::get('conversation/user/unread', 'ConversationController@getUnreadConversation');

    //PAYMENT - SWISH
    Route::post('payment/swish/pay/job/{type}', 'PaymentController@swishJobPay');//job resource
    Route::post('payment/swish/pay/choose', 'PaymentController@swishChoosePay');//applicant resource
    Route::get('payment/swish/complete/{orderId}', 'PaymentController@swishComplete');
    //PAYMENT - KLARNA
    Route::get('payment/klarna/complete/{orderId}', 'PaymentController@klarnaComplete');
    Route::post('payment/klarna/pay/job/{type}', 'PaymentController@klarnaJobPay');
    Route::post('payment/klarna/pay/choose', 'PaymentController@klarnaChoosePay');
    Route::post('payment/klarna/pay/subscribe', 'PaymentController@klarnaSubscriptionPay');
    Route::post('payment/klarna/subscribe/cancel', 'PaymentController@cancelSubscription');


    //NOTIFICATION
    Route::get('notification/get/{id}', 'NotificationController@getNotificationById');
    Route::get('notification/all/{page}', 'NotificationController@getAllNotifications');
    Route::get('notification/types/{type}/{page}', 'NotificationController@getSeparatedNotifications');
    Route::get('notification/delete/{id}', 'NotificationController@deleteNotification');
    Route::get('notification/unread', 'NotificationController@getUnreadCount');
    Route::get('notification/read/{id}', 'NotificationController@readNotification');

    //BOOKMARKS
    Route::get('bookmark/all/{page}', 'BookmarksController@listBookmarks');
    Route::post('bookmark/add', 'BookmarksController@addBookmark');
    Route::delete('bookmark/remove', 'BookmarksController@removeBookmark');

    //WISHLIST
    Route::post('wishlist/update', 'WishlistController@addUpdateWishlist');
    Route::get('wishlist/get', 'WishlistController@getWishlist');

    //CALCULATE PRICE
    Route::post('price/calculate', 'PaymentController@calculatePrice');

});
Route::middleware(['jwtMiddle'])->group(function () {
    Route::get('panel/user/get/{page}', 'UserController@getUserList');
});

