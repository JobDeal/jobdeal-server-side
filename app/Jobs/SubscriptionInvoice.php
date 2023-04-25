<?php

namespace App\Jobs;

use App\Http\InvoiceConst;
use App\Http\PayConst;
use App\Invoice;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade as PDF;

class SubscriptionInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try{

            $userId = $this->userId;

            $invoice = Invoice::where('user_id', '=', $this->userId)->where('type', '=', InvoiceConst::Subscription)->first();

            if (!$invoice) {
                $invoice = new Invoice();
                $invoice->user_id = $userId;
                $invoice->type = InvoiceConst::Subscription;
                $invoice->url = '';
                $invoice->save();

                $payments = \App\Payment::where('user_id', '=', $userId)->where('type', '=', PayConst::paySubscribe)->where('status', '=', "PAID")->get();
                $locale = $invoice->user->locale;

                Storage::put(storage_path("app/public/invoices/invoice_$invoice->id.html"), \Illuminate\Support\Facades\View::make('invoice', ['locale' => $locale, 'invoice' => $invoice, 'payments' => $payments])->render());

                PDF::setOptions(['dpi' => 150, 'defaultFont' => 'roboto']);
                PDF::setPaper("A4");
                $pdf = PDF::loadHTML(Storage::get(storage_path("app/public/invoices/invoice_$invoice->id.html")), "utf8");
                $pdf->save(storage_path("app/public/invoices/invoice_$invoice->id.pdf"));
                $title = "JobDeal | " . __("lang.invoice", [], $invoice->user->locale);
                $invoice->url = storage_path("app/public/invoices/invoice_$invoice->id.pdf");
                $invoice->save();

                $user = User::where('id', '=', $userId)->first();

                if ($user) {
                    $email = $user->email;


                    Mail::send('invoice', ['locale' => $locale, 'invoice' => $invoice, 'payments' => $payments], function ($message) use ($email, $title, $invoice) {
                        $message->to($email)->subject($title);
                        $message->attach(storage_path("app/public/invoices/invoice_$invoice->id.pdf"));
                        $message->from('jobdeal.info@gmail.com', 'JobDeal');

                    });
                }
            }

        } catch (\Exception $e) {

        }
    }
}
