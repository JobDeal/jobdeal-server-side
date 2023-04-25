<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

Route::get('/terms', function () {
    return view('terms.terms');
});

Route::get('/forgot-password/{token}', 'UserController@webResetPassword');

Route::get('test/payment', 'PaymentController@payKlarna');
Route::get('test/payment/finish/{token}', 'PaymentController@finishKlarnaPayment');


Route::get('test/invoice', function () {


    $job = \App\Job::where('id', '=', 378)->first();
    $payments = \App\Payment::where('job_id', '=', $job->id)->where('status', '=', "PAID")->get();
    $locale = $job->user->locale;

    $invoice = \App\Invoice::where('job_id', '=', 378)->first();


    Storage::put(storage_path("app/public/invoices/invoice_$invoice->id.html"), \Illuminate\Support\Facades\View::make('invoice', ['locale' => $locale, 'invoice' => $invoice, 'payments' => $payments])->render());

    PDF::setOptions(['dpi' => 150, 'defaultFont' => 'roboto']);
    PDF::setPaper("A4");
    $pdf = PDF::loadHTML(Storage::get(storage_path("app/public/invoices/invoice_$invoice->id.html")), "utf8");
    $pdf->save(storage_path("app/public/invoices/invoice_$invoice->id.pdf"));
    $title = "JobDeal | ".__("lang.invoice", [], $invoice->job->user->locale);
    $invoice->url = storage_path("app/public/invoices/invoice_$invoice->id.pdf");
    $invoice->save();

    Mail::send('invoice', ['locale' => $locale, 'invoice' => $invoice, 'payments' => $payments], function ($message) use ($title, $invoice) {
        $message->to('vukzdravkovic69@gmail.com')->subject($title);
        $message->attach(storage_path("app/public/invoices/invoice_$invoice->id.pdf"));

    });

    //return view('invoice', ['locale' => $locale, 'invoice' => $invoice, 'payments' => $payments]);

});
