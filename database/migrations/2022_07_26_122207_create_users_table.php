<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUsersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('name');
			$table->string('surname');
			$table->string('mobile');
			$table->string('email');
			$table->dateTime('email_verified_at')->nullable();
			$table->string('password');
			$table->string('address');
			$table->string('zip');
			$table->string('city');
			$table->string('country');
			$table->string('locale');
			$table->string('avatar')->nullable();
			$table->string('bank_id')->nullable();
			$table->string('klarna_token', 360)->nullable();
			$table->integer('role_id')->nullable();
			$table->string('active')->nullable();
			$table->string('remember_token', 100)->nullable();
			$table->timestamps();
			$table->text('about_me')->nullable();
			$table->string('lat')->nullable();
			$table->string('lng')->nullable();
			$table->string('timezone')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('users');
	}

}
