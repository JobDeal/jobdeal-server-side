<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateWishlistsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('wishlists', function(Blueprint $table)
		{
			$table->integer('id', true);
            $table->integer('user_id')->index('user_id');
			$table->float('from_price', 10, 0)->nullable();
			$table->float('to_price', 10, 0)->nullable();
			$table->integer('from_distance')->nullable();
			$table->integer('to_distance')->nullable();
			$table->point('location')->nullable();
			$table->text('categories');
			$table->string('country', 3)->index('country_wishlist_index');
			$table->boolean('is_active')->default(1);
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
		Schema::drop('wishlists');
	}

}
