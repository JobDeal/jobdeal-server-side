<?php

namespace App\Jobs;

use App\Http\PayConst;
use App\Invoice;
use App\Job;
use App\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CheckForInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected  $jobId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($jobId)
    {
        $this->jobId = $jobId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $jobId = $this->jobId;

            $invoice = Invoice::where('job_id', '=', $jobId)->first();

            if (!$invoice) {

                $job = Job::where('id', '=', $jobId)->first();

                if ($job) {

                    //check if all chosen applicants are paid, if they are create and send invoice

                    $chosenCount = $job->applicants()->where("choosed_at", "!=", null)->count();
                    $paidCount = Payment::where('job_id', '=', $jobId)->where('type', '=', PayConst::payChoose)->where('status', '=', 'PAID')->count();

                    if ($paidCount >= $chosenCount) {
                        //CreateAndSendInvoice::dispatch($jobId);
                    }
                }
            }
        } catch (\Exception $e) {

        }
    }
}
