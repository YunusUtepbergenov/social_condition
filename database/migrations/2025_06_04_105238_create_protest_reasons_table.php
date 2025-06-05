<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProtestReasonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('protest_reasons', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('district_code')->references('code')->on('districts')->onDelete('cascade');
            $table->date('date');
            $table->text('context');
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
        Schema::dropIfExists('protest_reasons');
    }
}
