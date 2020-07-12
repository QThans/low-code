<?php

namespace Thans\Bpm\Http\Controllers;

use Illuminate\Http\Request;
use Thans\Bpm\Models\Area;
use Thans\Bpm\Models\City;
use Thans\Bpm\Models\Province;

class CityDataController
{
    public function province()
    {
        return Province::get();
    }
    public function city(Request $request)
    {
        return City::where('province_code', $request->input('province', ''))->get();
    }
    public function area(Request $request)
    {
        return Area::where('city_code', $request->input('city', ''))->get();
    }
}
