<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePricesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('prices', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->float('list', 10, 0);
			$table->float('boost', 10, 0);
			$table->float('choose', 10, 0);
			$table->float('list_underbidder', 10, 0)->default(10);
			$table->float('difference', 10, 0);
			$table->float('subscribe', 10, 0);
			$table->float('speedy', 10, 0)->default(100);
			$table->string('country', 3);
			$table->string('currency', 3);
			$table->dateTime('from_date');
			$table->softDeletes();
			$table->timestamps();
			$table->float('swish_fee')->default(3.00);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('prices');
	}

}
