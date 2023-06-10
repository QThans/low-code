<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->string('store_tips')->nullable(true);
            $table->tinyInteger('type')->comment('0-数据表单 1-流程表单')->default(0);
            $table->integer('user_id');
            $table->integer('menu_id')->nullable();
            $table->tinyInteger('status')->comment('0-正常')->default(0);
            $table->integer('order')->nullable(true)->default(0);
            $table->tinyInteger('update_user_auth')->default(0);
            $table->string('update_tips')->nullable(true);
            $table->string('delete_tips')->nullable(true);
            $table->timestamps();
            $table->softDeletes();
        });
        //数据存储
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('form_id');
            $table->string('form_alias');
            $table->jsonb('submission');
            $table->integer('user_id');
            $table->integer('created_user_id');
            $table->integer('updated_user_id');
            $table->text('header');
            $table->integer('status')->default(10000);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('form_components', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('form_id');
            $table->jsonb('values');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('form_events', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('form_id');
            $table->string('name');
            $table->string('type');
            $table->longText('event');
            $table->timestamps();
            $table->softDeletes();
        });
        //表单数据表格
        Schema::create('form_tables', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('form_id');
            $table->string('title');
            $table->text('code');
            $table->jsonb('fields');
            $table->jsonb('filters');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('departments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('order')->default(0);
            $table->integer('parent_id')->default(0);
            $table->timestamps();
            $table->softDeletes();
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
            $table->integer('parent_id')->default(0);
            $table->string('name');
            $table->string('icon');
            $table->text('description')->nullable(true);
            $table->integer('order')->nullable(true)->default(0);
            $table->integer('user_id');
            $table->integer('menu_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::table('admin_users', function (Blueprint $table) {
            $table->tinyInteger('status')->comment('0-正常')->default(0);
        });
        DB::select(<<<EOD
        CREATE INDEX "submission" ON "form_submissions" USING gin (
            "submission" "pg_catalog"."jsonb_ops"
        );
EOD);
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
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('department_users');
        Schema::dropIfExists('form_tables');
        Schema::dropIfExists('apps');
        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
