<?php

namespace Thans\Bpm\Http\Controllers\DingTalk;

use App\Http\Controllers\Controller;
use Dcat\Admin\Admin;
use Dcat\Admin\Form\Builder;
use EasyDingTalk\Application;
use EasyDingTalk\Auth\OAuthClient;
use Thans\Bpm\Bpm;
use Thans\Bpm\Models\UserPlatformAuth;
use Dcat\Admin\Widgets\Alert;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class LoginController extends Controller
{
    public function callback()
    {
        try {
            $user = app('dingtalk')->oauth->use('default')->user();
        } catch (\Throwable $th) {
            cache(['dingtalk_errors' => '钉钉授权失败']);
            return redirect(admin_url('auth/login'));
        }
        if ($user['errcode'] !== 0) {
            cache(['dingtalk_errors' => '钉钉授权失败']);
            return redirect(admin_url('auth/login'));
        }
        $auth = UserPlatformAuth::where('unionid', $user['user_info']['unionid'])->where('oauth_name', 'dingtalk')->first();
        if (!$auth) {
            cache(['dingtalk_errors' => '该钉钉未绑定用户']);
            return redirect(admin_url('auth/login'));
        }
        Admin::guard()->loginUsingId($auth->user_id, true);
        return redirect('/admin');
    }

    public function bind()
    {
        if (Bpm::userDingTalk()) {
            admin_toastr('已绑定钉钉', 'info');
            return redirect(admin_url('/'));
        }
        $code = Request::input('code');
        if (!$code) {
            return app('dingtalk')->oauth->use('default')->setRedirectUrl(route('dingtalk.bind'))->withQrConnect()->redirect();
        }
        try {
            $user = app('dingtalk')->oauth->use('default')->user();
        } catch (\Throwable $th) {
            admin_toastr('钉钉授权失败', 'error');
            return redirect(admin_url('/'));
        }
        if ($user['errcode'] !== 0) {
            admin_toastr('钉钉授权失败', 'error');
            return redirect(admin_url('/'));
        }
        $auth = UserPlatformAuth::where('unionid', $user['user_info']['unionid'])->where('oauth_name', 'dingtalk')->first();
        if ($auth) {
            admin_toastr('该钉钉已绑定其他账户', 'error');
            return redirect(admin_url('/'));
        }
        UserPlatformAuth::create([
            'user_id' => Admin::user()['id'],
            'oauth_name' => 'dingtalk',
            'unionid' => $user['user_info']['unionid'],
            'openid' => $user['user_info']['openid'],
            'detail' => $user['user_info'],
        ]);
        admin_toastr('钉钉绑定成功', 'success');
        return redirect(admin_url('/'));
    }

    public function unbind()
    {
        try {
            Bpm::userDingTalk()->delete();
        } catch (\Throwable $th) {
        }
        return json_encode(['code' => 1, '钉钉解绑完成']);
    }
}
