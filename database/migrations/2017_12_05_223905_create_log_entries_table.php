<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_entries', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('source_class');
            $table->integer('source_id');
            $table->integer('field_id');
            $table->longText('log_field1')->nullable();
            $table->longText('log_field2')->nullable();
            $table->longText('log_field3')->nullable();
            $table->longText('log_field4')->nullable();
            $table->longText('log_field5')->nullable();
            $table->longText('log_field6')->nullable();
            $table->longText('log_field7')->nullable();
            $table->longText('log_field8')->nullable();
            $table->longText('log_field9')->nullable();
            $table->longText('log_field10')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('log_entries');
    }
}
