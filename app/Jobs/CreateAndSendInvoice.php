<?php

namespace App\Jobs;

use App\Http\Helper;
use App\Http\PayConst;
use App\Invoice;
use App\Job;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Validation\Rules\In;
use LaravelDaily\Invoices\Classes\InvoiceItem;
use LaravelDaily\Invoices\Classes\Party;

class CreateAndSendInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobId;
    protected $paymentType;
    protected $paymentProcessor;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($jobId, $paymentType, $paymentProcessor)
    {
        $this->jobId = $jobId;
        $this->paymentType = $paymentType;
        $this->paymentProcessor = $paymentProcessor;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $paymentType = $this->paymentType;

        $jobId = $this->jobId;

        $job = Job::where('id', '=', $jobId)->first();
        $invoice = Invoice::where('job_id', '=', $jobId)->where('payment_type', '=', $paymentType)->first();

        if (!$invoice) {
            \Log::debug("Invoice Created!");
            $invoice = new Invoice();
            $invoice->job_id = $jobId;
            $invoice->user_id = $job->user_id;
            $invoice->url = '';
            $invoice->payment_type = $paymentType;
            $invoice->save();


            $locale = $invoice->job->user->locale;
            $payments = \App\Payment::where('job_id', '=', $invoice->job_id)->where('type', '=', $paymentType)->where('status', '=', "PAID")->limit(1)->get();

            App::setLocale($locale);


            $client = new Party([
                'name' => 'JB Deal AB',
                'phone' => 'info@jobdeal.com',
                'custom_fields' => [
                    'organisationsnummer' => '559257-1953',
                ],
            ]);

            $customer = new Party([
                'name' => $invoice->user->name . " " . $invoice->user->surname,
                'address' => $invoice->user->address . ", " . $invoice->user->zip . " " . $invoice->user->city,
                'custom_fields' => [
                    'order number' => $jobId,
                ],
            ]);

            $items = [];
            $taxAmount = 0;

            foreach ($payments as $payment) {

                if ($this->paymentProcessor == PayConst::paymentProcessorKlarna) //Klarna payment amount is multiple by 100
                    $paymentAmount = $payment->amount / 100;
                else
                    $paymentAmount = $payment->amount - Helper::getSwishFee();

                $taxAmount += ($paymentAmount) * 0.2;
                array_push($items, (new InvoiceItem())
                    ->title(Helper::getPaymentType($payment->type, $locale))
                    ->pricePerUnit(($paymentAmount) * 0.8)
                    ->quantity(1)
                    ->taxByPercent(25)
                );
            }

            array_push($items, (new InvoiceItem())->title(__('lang.transactionFee', [], $locale))->pricePerUnit(Helper::getSwishFee())->taxByPercent(0));


            /* $notes = [
                 "Moms är inkluderad med $taxAmount SEK"
             ];
             $notes = implode("<br>", $notes);*/

            $inv = \LaravelDaily\Invoices\Invoice::make('receipt')
                // ability to include translated invoice status
                // in case it was paid
                ->sequence($invoice->id)
                ->serialNumberFormat('#{SEQUENCE}')
                ->seller($client)
                ->buyer($customer)
                ->date(Carbon::parse($invoice->created_at))
                ->dateFormat('d.m.Y.')
                ->currencyCode($payment->currency)
                ->currencySymbol($payment->currency)
                ->currencyFormat('{VALUE} {SYMBOL}')
                ->currencyThousandsSeparator('.')
                ->currencyDecimalPoint(',')
                ->filename('invoice_' . $invoice->id)
                ->addItems($items)
                //->notes($notes)
                ->payUntilDays(0)
                ->logo(asset('images/jobdeal_logo_green.png'))
                // You can additionally save generated invoice to configured disk
                ->save('local');

            $link = $inv->url();

            $invoice->url = $link;
            $invoice->save();
            \Log::debug("Invoice link: " . $link);

            $email = $invoice->job->user->email;
            $title = "JobDeal | " . __("lang.invoice", [], $invoice->job->user->locale);

            Mail::raw(__('lang.invoice_body', ["link" => config("conf.url") . $link], $locale), function ($message) use ($email, $title, $invoice) {
                $message->to($email)->subject($title);
                $message->attach('/var/www/jdSpace/invoice_' . $invoice->id . '.pdf');
                $message->from('jobdeal.info@gmail.com', 'JobDeal');
            });


            /*Storage::put(storage_path("app/public/invoices/invoice_$invoice->id.html"), \Illuminate\Support\Facades\View::make('invoice', ['locale' => $locale, 'invoice' => $invoice, 'payments' => $payments])->render());

            PDF::setOptions(['dpi' => 150, 'defaultFont' => 'roboto']);
            PDF::setPaper("A4");
            $pdf = PDF::loadHTML(Storage::get(storage_path("app/public/invoices/invoice_$invoice->id.html")), "utf8");
            $pdf->save(storage_path("app/public/invoices/invoice_$invoice->id.pdf"));
            $title = "JobDeal | " . __("lang.invoice", [], $invoice->job->user->locale);
            $invoice->url = storage_path("app/public/invoices/invoice_$invoice->id.pdf");
            $invoice->save();

            $email = $invoice->job->user->email;


            Mail::send('invoice', ['locale' => $locale, 'invoice' => $invoice, 'payments' => $payments], function ($message) use ($email, $title, $invoice) {
                $message->to($email)->subject($title);
                $message->attach(storage_path("app/public/invoices/invoice_$invoice->id.pdf"));
                $message->from('jobdeal.info@gmail.com', 'JobDeal');

            });*/
        }

    }
}
