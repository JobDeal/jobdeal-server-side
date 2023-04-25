<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePaymentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('payments', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->string('provider', 60);
			$table->integer('job_id')->nullable();
			$table->integer('doer_id')->nullable();
			$table->integer('user_id')->nullable();
			$table->float('amount', 10, 0);
			$table->string('currency', 3);
			$table->string('status', 60);
			$table->string('error', 180)->nullable();
			$table->text('error_message', 65535)->nullable();
			$table->integer('type');
			$table->string('ref_id', 300);
			$table->string('swish_id', 300)->nullable();
			$table->string('description', 1000)->nullable();
			$table->softDeletes();
			$table->timestamps();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('payments');
	}

}
