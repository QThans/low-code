<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'bpm',
    'namespace'     => 'Thans\\Bpm\\Http\\Controllers',
], function () {
    Route::group(['prefix' => 'form'], function () {
        Route::resource('builder', 'FormBuilderController');
        Route::get('events', 'FormBuilderController@formEVents')->name('bpm.formEvents');
    });
    Route::resource('department', 'DepartmentController');
});
