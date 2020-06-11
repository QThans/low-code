<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 创建BPM表单配置表
 * @package 
 */
class Bpm extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('alias');
            $table->text('description');
            $table->tinyInteger('type')->comment('0-数据表单 1-流程表单')->default(0);
            $table->integer('user_id');
            $table->tinyInteger('status')->comment('0-正常')->default(0);
            $table->timestamps();
        });
        Schema::create('form_components', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('form_id');
            $table->jsonb('components');
            $table->timestamps();
        });
        Schema::create('form_events', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('form_id');
            $table->string('name');
            $table->string('type');
            $table->longText('event');
            $table->timestamps();
        });
        Schema::create('form_roles', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('form_id');
            $table->string('name');
            $table->text('description');
            $table->string('actions');
            $table->text('fields');
            $table->jsonb('datas');
            $table->timestamps();
        });
        Schema::create('form_role_users', function (Blueprint $table) {
            $table->integer('role_id');
            $table->integer('user_id');
            $table->timestamps();
        });
        Schema::create('form_departments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('order')->default(0);
            $table->integer('parent_id');
            $table->timestamps();
        });
        Schema::create('form_department_users', function (Blueprint $table) {
            $table->integer('department_id');
            $table->integer('user_id');
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
        Schema::dropIfExists('forms');
        Schema::dropIfExists('form_components');
        Schema::dropIfExists('form_events');
        Schema::dropIfExists('form_roles');
        Schema::dropIfExists('form_role_users');
        Schema::dropIfExists('form_departments');
        Schema::dropIfExists('form_department_users');
    }
}
