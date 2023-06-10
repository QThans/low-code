<?php

use Dcat\Admin\Admin;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'dingtalk',
    'namespace'     => 'Thans\\Bpm\\Http\\Controllers\\DingTalk',
], function () {
    Route::get('login/callback', 'LoginController@callback')->name('dingtalk.callback')->middleware('web');
});

