<?php

namespace App\Http;


abstract class NotificationConst
{
    const DoerBid = 1;
    const BuyerAccepted  = 2;
    const RateDoer = 3;
    const RateBuyer = 4;
    const WishlistJob = 5;
    const PaymentSuccess = 6;
    const PaymentError = 7;

    const GENERAL_CHANNEL_ID = "General";
}
