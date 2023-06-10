<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DataRole extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('data_roles', function (Blueprint $table) {
            $table->integer('role_id');
            $table->integer('form_id');
            $table->string('range')->nullable()->comment('self section all');
            $table->text('fields')->nullable()->comment('expanded field');
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
        Schema::dropIfExists('data_roles');
    }
}
