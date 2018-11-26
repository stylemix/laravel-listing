<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBooksTable extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return  void
	 */
	public function up()
	{
		Schema::create('books', function (Blueprint $table) {
			$table->increments('id');
			$table->string('title');
			$table->timestamps();
		});

		Schema::create('book_data', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('entity_id');
			$table->foreign('entity_id')->references('id')->on('books')->onDelete('cascade')->onUpdate('cascade');
			$table->string('lang', 5)->nullable();
			$table->string('name', 32);
			$table->longText('value');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return  void
	 */
	public function down()
	{
		Schema::dropIfExists('book_data');
		Schema::dropIfExists('books');
	}
}
