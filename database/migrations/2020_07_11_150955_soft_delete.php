<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SoftDelete extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('form_components', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('form_events', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('departments', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('form_tables', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('apps', function (Blueprint $table) {
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
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
        Schema::table('form_components', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
        Schema::table('form_events', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
        Schema::table('form_tables', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
        Schema::table('apps', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
    }
}
