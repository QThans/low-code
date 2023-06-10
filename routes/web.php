<?php

use Illuminate\Support\Facades\Route;
use Thans\Bpm\Http\Controllers\AuthController;
use Thans\Bpm\Http\Controllers\DataRoleController;
use Thans\Bpm\Http\Controllers\DingtalkLoginController;
use Thans\Bpm\Http\Controllers\RoleController;
use Thans\Bpm\Http\Controllers\UserController;

Route::group([
    'prefix' => 'bpm',
    'namespace'     => 'Thans\\Bpm\\Http\\Controllers',
    'middleware' => config('admin.route.middleware'),
], function () {
    Route::resource('forms', 'FormBuilderController');
    Route::group(['prefix' => 'form'], function () {
        Route::get('events', 'FormBuilderController@formEVents')->name('bpm.formEvents');
        // Route::get('auth/department/actions', 'FormBuilderController@formDepartmentActions')->name('bpm.formDepartmentActions');
        // Route::get('auth/user/actions', 'FormBuilderController@formUserActions')->name('bpm.formUserActions');
        Route::get('/', 'FormController@index')->name('bpm.baseurl');
    });
    Route::resource('department/user', 'DepartmentUserController');
    Route::resource('department', 'DepartmentController');
    Route::resource('apps', 'AppsController');
    Route::resource('/{alias}/form', 'BpmController');
    Route::delete('/{alias}/form/{form}', 'BpmController@destroy')->name('form.destroy');
    Route::post('/form/{id}/temp', 'FormController@temp')->name('bpm.formTemp');
    Route::any('/form/{id}/submission',  'FormController@submission')->name('bpm.formSubmission');
    Route::get('/form/{id}', 'FormController@detail')->name('bpm.formDetail');
    //文件上传
    Route::any('/file',  'FileController@handle')->name('bpm.file');
});

//覆盖登录页面操作
Route::post('auth/login', [AuthController::class, 'postLogin']);
Route::resource('auth/users', UserController::class);
Route::resource('auth/roles', RoleController::class);
Route::get('data/roles/{roleId}', [DataRoleController::class, 'index']);
Route::post('data/roles', [DataRoleController::class, 'store']);

Route::group([
    'prefix' => 'dingtalk',
    'namespace'     => 'Thans\\Bpm\\Http\\Controllers\\DingTalk',
    'middleware' => config('admin.route.middleware'),
], function () {
    Route::get('unbind', 'LoginController@unbind')->name('dingtalk.unbind');
    Route::get('bind', 'LoginController@bind')->name('dingtalk.bind');
});
