<?php

namespace Tests\Feature\Seed\Notification\Doer;

use App\Applicant;
use Illuminate\Database\Seeder;

class ApplicantsTableSeeder extends Seeder
{
    public function run()
    {
        foreach ($this->getApplicants() as $applicant) {
            factory(Applicant::class)->create($applicant);
        }
    }

    private function getApplicants()
    {
        return [
            [
                "user_id" => 2,
                "job_id" => 1,
                "choosed_at" => new \DateTime()
            ],
            [
                "user_id" => 2,
                "job_id" => 2,
            ],
            [
                "user_id" => 2,
                "job_id" => 3,
            ],
//            [
//                "user_id" => 2,
//                "job_id" => 4,
//            ]
        ];
    }
}
