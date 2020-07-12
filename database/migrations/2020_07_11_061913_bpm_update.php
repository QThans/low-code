<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BpmUpdate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //修复postgresql问题
        DB::select('SELECT nextval(\'"admin_permissions_id_seq"\'::regclass)');
        DB::select('SELECT setval(\'"admin_permissions_id_seq"\', (SELECT MAX(id) FROM "admin_permissions")+1);');
        Schema::table('apps', function (Blueprint $table) {
            $table->integer('menu_id')->nullable();
        });
        Schema::table('forms', function (Blueprint $table) {
            $table->integer('menu_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropColumn('menu_id');
        });
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('menu_id');
        });
    }
}
