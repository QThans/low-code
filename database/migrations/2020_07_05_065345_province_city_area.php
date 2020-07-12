<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Thans\Bpm\Bpm;

class ProvinceCityArea extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->char('code', 2);
            $table->string('name', 20);
            $table->unique('code', 'unique_province_code');
        });
        Schema::create('cities', function (Blueprint $table) {
            $table->char('code', 4);
            $table->string('name', 20);
            $table->char('province_code', 2);
            $table->unique('code', 'unique_city_code');
        });
        Schema::create('areas', function (Blueprint $table) {
            $table->char('code', 6);
            $table->string('name', 20);
            $table->char('city_code', 4);
            $table->char('province_code', 2);
            $table->unique('code', 'unique_area_code');
        });
        $provinces = Bpm::getProvinces();
        DB::table('provinces')->insert($provinces);
        $cities = Bpm::getCities();
        DB::table('cities')->insert($cities);
        $areas = Bpm::getAreas();
        DB::table('areas')->insert($areas);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('areas');
    }
}
