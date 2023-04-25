<?php
/**
 * Created by PhpStorm.
 * User: macbook
 * Date: 3/15/19
 * Time: 1:10 PM
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Pagination Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by the paginator library to build
    | the simple pagination links. You are free to change them to anything
    | you want to customize your views to better match your application.
    |
    */

    'swishBoostMessage' => "Boost job :id",
    //NOTIFICATIONS
    'doerBid_description' => "Someone just applied for your job. Check it out now!",
    'doerBid_title' => "You have a new bidder",
    'rateDoer_title' => "Rate doer",
    'rateDoer_description' => "Do you like how your job is done? Rate doer now.",
    'rateBuyer_title' => "Rate buyer",
    'rateBuyer_description' => "Do you like the job you done? Rate buyer now.",
    'doerGotJob_title' => "You got the job",
    'doerGotJob_description' => "You just have been choosen for a job. Buyer will contact you as soon as possible.",
    'wishlistJob_title' => "Job for you",
    'wishlistJob_description' => "New job for you! Check it out.",
    'paymentSubscription_title' => "Payment subscription",
    'paymentSubscription_description' => "Premium user subscription is charged.",
    'paymentError_title' => "Payment error",
    'paymentError_description' => "Some error occurred while trying to renew subscription.",
    'wishlist_subscription_not_exists' => "You don't have active subscription. In order to use wishlist you must have active subscription.",
    //BANK ID ERRORS
    'rfa3' => 'Action cancelled. Please try again.',
    'rfa8' => 'The BankID app is not responding. Please check that the program is started and that you have internet access. If you don’t have a valid BankID you can get one from your bank. Try again.',
    'rfa16' => 'The BankID you are trying to use is revoked or too old. Please use another BankID or order a new one from your internet bank.',
    'rfa6' => 'Action cancelled.',
    'rfa17' => 'The BankID app couldn’t be found on your computer or mobile device. Please install it and order a BankID from your internet bank. Install the app from your app store or https://install.bankid.com.',



    //INVOICE
    "invoice" => "Receipt",
    "issueDate" => "Issue date",
    "listOfDoers" => "Show list of doers",
    "payUnderbidder" => "Unlock contact of lower bid doers",
    "boostJob" => "Boost job",
    "speedyJob" => "Speedy job",
    "boostSpeedyJob" => "Boost speedy job",
    "chooseDoer" => "Chosen doer",
    "sum" => "Total",
    "job" => "Job",
    "buyer" => "Buyer",
    "type" => "Type",
    "price" => "Price",
    "verificationSms" => "Job Deal verification code is :code",
    "mediationFee" => "Förmedlingsavgift",
    "transactionFee" => "Transaktionsavgift",


    //priceCalculation

    "listPaymentDescription" => "To see doer contacts, please choose a payment method.",
    "boostPaymentDescription" => "To boost your add, please choose a payment method",
    "speedyPaymentDescription" => "If you need job to be done fast, please choose a payment method",
    "speedyBoostPaymentDescription" => "If you need job to be done fast & you want to boost your add, please choose a payment method",
    "choosePaymentDescriptionDiff" => ":choosePerc % of the offered price + :diffPerc % of price difference between your offered price and bid price + :swishFee swish fee. \n To choose doer, please choose a payment method.",
    "choosePaymentDescriptionDiffNoChoose" => ":diffPerc % of price difference between your offered price and bid price + :swishFee swish fee. \n To choose doer, please choose a payment method.",
    "choosePaymentDescription" => ":choosePerc % of the offered price + :swishFee swish fee. \n To choose doer, please choose a payment method.",
    "choosePaymentDescriptionNoChoose" => "To choose doer, please choose a payment method.",
    "listUnderbidderPaymentDescription" => "To be able to see detail information about Doer's who offered a lower bid, please choose a payment method.",

    "forgot_password" => "Forgot password",
    "forgot_password_desc" => "Click on link to reset your password: :link",

    "expired_at_error" => "Expired at can not be in the past. Make sure you entered right date and time for expired at.",

    "invoice_body" => "Invoice is available in the attachment of this mail or you can download it by click on link: :link"
];
