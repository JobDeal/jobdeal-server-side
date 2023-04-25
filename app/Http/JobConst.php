<?php

namespace App\Http;


abstract class JobConst
{
    const Standard = 1;
    const Speedy  = 2;

    const StatusNormal = 1;
    const StatusSeeApplicants = 2;
    const StatusDoerChoosed = 3;
}
