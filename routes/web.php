<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'bpm',
    'namespace'     => 'Thans\\Bpm\\Http\\Controllers',
], function () {
    Route::group(['prefix' => 'form'], function () {
        Route::resource('builder', 'FormBuilderController');
        Route::get('events', 'FormBuilderController@formEVents')->name('bpm.formEvents');
        Route::get('auth/department/actions', 'FormBuilderController@formDepartmentActions')->name('bpm.formDepartmentActions');
        Route::get('auth/user/actions', 'FormBuilderController@formUserActions')->name('bpm.formUserActions');
    });
    Route::resource('department', 'DepartmentController');
    Route::resource('apps', 'AppsController');
});
