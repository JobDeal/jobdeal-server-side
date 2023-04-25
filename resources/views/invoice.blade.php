<!DOCTYPE html>
<!--
  Invoice template by invoicebus.com
  To customize this template consider following this guide https://invoicebus.com/how-to-create-invoice-template/
  This template is under Invoicebus Template License, see https://invoicebus.com/templates/license/
-->
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>JobDeal</title>

    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="description" content="CarGo Fresh @lang("lang.invoice")">

    <meta name="template-hash" content="a4c064837d76a40837039b1d1e103187">

    <style>
        *{
            margin:0px;
            padding:0px;
        }
        .background-green{
            height: 100px;
            padding-top:45px;
            border-bottom: 3px solid #3bb2b8;
            background: rgba(0,217,173,1);
            background-size: 100% auto;
            text-align: center;
            color: white;
            width:100%;

        }


        .background-green img{
            display: block;
            width:300px;

        }
        .background-green table{
            margin-top:10px;
        }
        table td{

            width:50%;
        }
        table th{
            width:50%;
        }
        .table-two td{
            padding-top:3px;
            padding-bottom:3px;
            border-bottom: 1px solid #737373 !important;
        }

        td{
            color:#0a0302 !important;
        }
        .first-table {
            width:55% ;
        }
        @media only screen and (max-width:767px) {
            .first-table {
                width:100% !important;
            }
            .background-green img{
                width:200px;
            }
            .background-green table{
                margin-top:10px;
            }

            .first-table td{
                font-size: 11.5px !important;
            }
        }


    </style>

</head>
<body>
<div class="background-green">
    <table width="100%;">
        <tr>
            <th style="width: 100%;"></th>
        </tr>
        <tr>
            <td style=" width: 100% ;" align="center">
                <img src="{{asset('images/JobDeal-white-long-small.png')}}">
            </td>
        </tr>
    </table>

</div>

<br>
<table class="first-table" width="55%" style="margin:0px auto;" >
    <thead>
    <tr>
        <th></th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td style="text-align: left; "> <span style="color:#CF5637 !important;">{{__('lang.invoice', [], $locale)}}: {{$invoice->id}} / {{\Carbon\Carbon::now()->format('Y')}}</span>
            <br>
            @if($invoice->type == \App\Http\InvoiceConst::Job)
                <span style="color:#CF5637 !important;;"> {{__('lang.job', [], $locale)}}: #{{$invoice->job_id}} {{$invoice->job->name}}</span><br>
            @endif

            <span >{{__('lang.issueDate', [], $locale)}}:</span><span>{{\Carbon\Carbon::now()->format("d/m/y")}}</span>
        </td>
        <td style="text-align: right">
            @if($invoice->type == 1)
                <h5 style="color:#CF5637 !important;;">{{__('lang.buyer', [], $locale)}}</h5>

            @endif
            @if($invoice->type == 1)
                <bold>{{$invoice->job->user->name}} {{$invoice->job->user->surname}}</bold> <br>
                {{$invoice->job->address}} <br>
            @endif
            @if($invoice->type == 2)
                <h5 style="color:#CF5637;">{{__('lang.buyer', [], $locale)}}</h5>
                <bold>{{$invoice->user->name}} {{$invoice->user->surname}}</bold> <br>
                {{$invoice->user->address}}

            @endif
        </td>

    </tr>

    </tbody>
</table>

<br>

<table  width="100%"  class="table-two">
    <tr style="background-color: #737373; color:#ffffff !important;" ;>
        <th style="text-align: center" style=" color : #ffff">{{strtoupper(__('lang.type', [], $locale))}}</th>
        <th style="text-align: center" style=" color : #ffff">{{strtoupper(__('lang.price', [], $locale))}}</th>
    </tr>
    @php
        $totalPrice = 0;
        $currency = "SEK";
    @endphp
    @foreach($payments as $payment)
        @php
            if ($payment->provider == 'Klarna') {
            $totalPrice = $totalPrice + ($payment->amount / 100);
            $price = $payment->amount / 100;
            } else {
            $price = $payment->amount;
            }
            $currency = $payment->currency;
        @endphp
        <tr>
            <td style="text-align: center">
                {{\App\Http\Helper::getPaymentType($payment->type, $locale)}}
                @php
                    if ($payment->type == \App\Http\PayConst::payChoose){
                     echo ' - '.$payment->doer->name.' '.$payment->doer->surname;
                    }
                @endphp
            </td>
            <td style="text-align: center">{{number_format($price,2)}} {{$payment->currency}}</td>
        </tr>
    @endforeach

</table>
<table width="100%;">
    <tr>
        <th style="width: 100%;"></th>
    </tr>
    <tr>
    <td style=" width: 100% ;" align="right"> <span style=" background-color: #737373; color:#ffffff !important; font-size: 17px;">{{strtoupper(__('lang.mediationFee', [], $locale))}} : 10% x {{number_format($totalPrice, 2)}} {{$currency}} = {{number_format($totalPrice * 0.10, 2)}} {{$currency}}</span></td>
    </tr>
    <tr>
    <td style=" width: 100% ;" align="right"> <span style=" background-color: #737373; color:#ffffff !important; font-size: 17px;">{{strtoupper(__('lang.transactionFee', [], $locale))}} : {{number_format(3, 2)}} {{$currency}}</span></td>
    </tr>
    <tr>
    <td style=" width: 100% ;" align="right"> <span style=" background-color: #737373; color:#ffffff !important; font-size: 17px;">{{strtoupper(__('lang.sum', [], $locale))}} : {{number_format($totalPrice, 2)}} {{$currency}}</span></td>
    </tr>
</table>




<script src="http://cdn.invoicebus.com/generator/generator.min.js?data=true"></script>
</body>
</html>
