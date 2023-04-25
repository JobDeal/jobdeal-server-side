<?php

namespace App\Jobs;

use App\Invoice;
use App\Job;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class FindExpiredJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $jobs = Job::where('id', '>', 380)->where('expire_at', '<=', Carbon::now()->toDateTimeString())->get();

        foreach ($jobs as $job) {

            $invoice = Invoice::where('job_id', '=', $job->id)->first();

            if (!$invoice) {
                //CreateAndSendInvoice::dispatch($job->id);
            }
        }
    }
}
