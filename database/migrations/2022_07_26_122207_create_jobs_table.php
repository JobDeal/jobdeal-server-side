<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateJobsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('jobs', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('user_id');
			$table->string('name');
			$table->string('description', 1500);
			$table->float('price')->index('price');
			$table->string('address');
			$table->integer('category_id')->index('category_id');
			$table->integer('status')->default(1)->index('status_job_index');
			$table->integer('applicant_count')->default(1);
			$table->boolean('is_boost')->default(0);
			$table->boolean('is_speedy')->default(0);
			$table->boolean('is_listed')->default(0);
			$table->boolean('is_underbidder_listed')->default(0);
			$table->boolean('is_rate_sent')->default(0);
			$table->string('location_string')->nullable();
			$table->geometry('location');
			$table->boolean('is_active')->default(1)->index('is_active');
			$table->boolean('is_offensive')->nullable()->default(0);
			$table->string('country', 5)->index('country_index');
			$table->dateTime('expire_at')->index('expire_at_index');
			$table->timestamps();
			$table->boolean('is_delivery')->default(0);
			$table->dateTime('help_on_the_way')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('jobs');
	}

}
