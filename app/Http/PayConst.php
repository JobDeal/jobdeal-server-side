<?php

namespace App\Http;


abstract class PayConst
{
    const payList = 1;
    const payBoost  = 2;
    const paySpeedy = 3;
    const payBoostSpeedy = 4;
    const payChoose = 5;
    const paySubscribe = 6;
    const payUnderbidder = 7; //to be able to open applicants that under bid the offer

    const paymentProcessorSwish = 'swish';
    const paymentProcessorKlarna = 'klarna';
}
