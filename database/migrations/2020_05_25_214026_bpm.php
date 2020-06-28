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
            $table->integer('apps_id');
            $table->string('alias');
            $table->text('description')->nullable(true);
            $table->tinyInteger('type')->comment('0-数据表单 1-流程表单')->default(0);
            $table->integer('user_id');
            $table->tinyInteger('status')->comment('0-正常')->default(0);
            $table->integer('order')->nullable(true)->default(0);
            $table->timestamps();
        });
        //数据存储
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('form_id');
            $table->string('form_alias');
            $table->jsonb('submission');
            $table->integer('user_id');
            $table->integer('updated_user_id');
            $table->text('header');
            $table->timestamps();
        });

        Schema::create('form_components', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('form_id');
            $table->jsonb('values');
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
        //表单授权部门
        Schema::create('form_departments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('form_id');
            $table->integer('department_id');
            $table->string('actions'); //权限类型权限类型:store\edit\show\create\update\destroy
            $table->timestamps();
            $table->index(['form_id', 'department_id']);
        });
        //表单授权用户
        Schema::create('form_users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('form_id');
            $table->integer('user_id');
            $table->string('actions'); //权限类型:store\edit\show\create\update\destroy
            $table->timestamps();
            $table->index(['form_id', 'user_id']);
        });
        //表单数据表格
        Schema::create('form_tables', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('form_id');
            $table->text('code');
            $table->jsonb('fields');
            $table->jsonb('filters');
            $table->timestamps();
        });
        Schema::create('departments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('order')->default(0);
            $table->integer('parent_id')->default(0);
            $table->timestamps();
        });
        //用户所属部门
        Schema::create('department_users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('department_id');
            $table->integer('user_id');
            $table->timestamps();
        });

        Schema::create('apps', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('icon');
            $table->text('description')->nullable(true);
            $table->integer('order')->nullable(true)->default(0);
            $table->integer('user_id');
            $table->timestamps();
        });
        //应用授权部门
        Schema::create('apps_departments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('apps_id');
            $table->integer('department_id');
            $table->index(['apps_id', 'department_id']);
            $table->timestamps();
        });
        //应用授权用户
        Schema::create('apps_users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('apps_id');
            $table->integer('user_id');
            $table->index(['apps_id', 'user_id']);
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
        Schema::dropIfExists('form_departments');
        Schema::dropIfExists('form_users');
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('department_users');
        Schema::dropIfExists('form_tables');
        Schema::dropIfExists('apps_departments');
        Schema::dropIfExists('apps_users');
        Schema::dropIfExists('apps');
    }
}
