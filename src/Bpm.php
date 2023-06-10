<?php

namespace Thans\Bpm;

use Dcat\Admin\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Thans\Bpm\Models\Apps;
use Thans\Bpm\Models\Department;
use Thans\Bpm\Models\DepartmentUsers;
use Thans\Bpm\Models\User;
use Dcat\Admin\Layout\Menu;
use EasyDingTalk\Application;
use Thans\Bpm\Http\Controllers\BpmController;
use Thans\Bpm\Models\DataRole;
use Thans\Bpm\Models\Form;
use Thans\Bpm\Models\Role;

class Bpm
{
    const NAME = 'bpm';

    /**
     * 版本号.
     *
     * @var string
     */
    const VERSION = '1.0.0-dev';

    public $serviceProvider = BpmServiceProvider::class;

    public $composer = __DIR__ . '/../composer.json';

    public $assets = __DIR__ . '/../resources/assets';

    public $views = __DIR__ . '/../resources/views';

    public $lang = __DIR__.'/../resources/lang';


    /**
     * 版本.
     *
     * @return string
     */
    public static function longVersion()
    {
        return sprintf('Bpm <comment>version</comment> <info>%s</info>', static::VERSION);
    }
    public static function getAppsByDepartmentsAuth()
    {
        $departments = Department::childrenIds();
        $departmentsChildIds = [];
        foreach (self::myDepartments()->pluck('id')->toArray() as $key => $value) {
            $departmentsChildIds = array_merge($departmentsChildIds, $departments[$value]);
        }
        return Apps::getByDepartments($departmentsChildIds)->toArray();
    }

    public static function getAppsByUsersAuth()
    {
        return Apps::getByUserId(Admin::user()->id)->toArray();
    }

    // public static function loadApps()
    // {
    //     $apps = Bpm::getAppsByDepartmentsAuth() + Bpm::getAppsByUsersAuth();
    //     $forms = [];
    //     $menus = collect($apps)->sortBy('order')->map(function ($value, $key) use (&$forms) {
    //         $value['title'] = $value['name'];
    //         $value['uri'] = '';
    //         $value['parent_id'] = 0;
    //         $forms = array_merge(Form::getByNoAuth($value['id'])->map(function ($val) use ($value) {
    //             return [
    //                 'id' => 'f' . $val['id'],
    //                 'parent_id' => $value['id'],
    //                 'title' => $val['name'],
    //                 'icon' => '',
    //                 'uri' => action([BpmController::class, 'index'], ['id' => $val['id']]),
    //             ];
    //         })->toArray(), $forms);
    //         return $value;
    //     })->toArray();
    //     $menus = array_merge(collect($forms)->sortBy('order')->toArray(), $menus);
    //     Admin::menu(function (Menu $menu) use ($menus) {
    //         $menu->add($menus);
    //     });
    // }
    public static function myDepartments()
    {
        return Department::getByUserId(Admin::user()->id);
    }
    public static function getCities()
    {
        return json_decode(file_get_contents(__DIR__ . '/../data/cities.json'), true);
    }
    public static function getProvinces()
    {
        return json_decode(file_get_contents(__DIR__ . '/../data/provinces.json'), true);
    }
    public static function getAreas()
    {
        return json_decode(file_get_contents(__DIR__ . '/../data/areas.json'), true);
    }
    public static function getGridsForm()
    {
        return json_decode(file_get_contents(__DIR__ . '/../data/forms/grid.json'), true);
    }

    public static function getUserIdField()
    {
        return json_decode(file_get_contents(__DIR__ . '/../data/fields/user_id.json'), true);
    }

    public static function dataAuth($formId, $fieldsOptions, $submissionName = 'submission')
    {
        if (Admin::user('role')->inRoles(Role::ADMINISTRATOR_ID)) {
            return [];
        }
        $sql = [];
        //数据域权限分配
        $user_id = Admin::user()->id;
        $roles = Admin::user('role')->roles()->pluck('id');
        $dataRoles = DataRole::whereIn('role_id', $roles)->where('form_id', $formId)->select(['range', 'fields'])->get()->toArray();
        if (!$dataRoles) {
            //不存在授权，默认自己。即：创建人或更新人为自己
            $sql[] = 'user_id = ' . $user_id;
            //whether use the last user to update to auth
            if (config('bpm.auth.updated_user_as_auth', true)) {
                $sql[] = 'updated_user_id = ' . $user_id;
            }
            return $sql;
        }
        foreach ($dataRoles as $key => $value) {
            $users = [];
            switch ($value['range']) {
                case 'all':
                    return [];
                case 'self':
                    $users = [$user_id];
                    break;
                case 'section':
                    $departments = DepartmentUsers::where('user_id', $user_id)->pluck('department_id');
                    $users = DepartmentUsers::whereIn('department_id', $departments)->pluck('user_id')->toArray();
                    break;
                default:
                    $users = [];
                    break;
            }
            $sql[] = "user_id in (" . implode(',', $users) . ")";
            if (config('bpm.auth.updated_user_as_auth', true)) {
                $sql[] = "updated_user_id in (" . implode(',', $users) . ")";
            }
            if (!$value['fields']) {
                break;
            }
            $fields = json_decode($value['fields'], true);
            foreach ($fields as $f) {
                if (!$f) {
                    continue;
                }
                if (isset($fieldsOptions[$f]) && isset($fieldsOptions[$f]['data']['resource']) && $fieldsOptions[$f]['data']['resource'] == 'users') {
                    //查看组件类型
                    if (isset($fieldsOptions[$f]['multiple']) && $fieldsOptions[$f]['multiple']) {
                        $sql[] = "($submissionName ->'$f')::jsonb @> '[" . implode(',', $users) . "]'";
                    } else {
                        $quotatnion = $users;
                        foreach ($quotatnion as $k => $u) {
                            $quotatnion[$k] = "'" . $u . "'";
                        }
                        $sql[] = "($submissionName->>'$f') in (" . implode(',', $quotatnion) . ")";
                    }
                }
            }
        }
        return $sql;
    }
    public static function userDingTalk()
    {
        $dingtalk = User::where('id', Admin::user()['id'])->with('dingtalk')->first()->dingtalk;
        return $dingtalk;
    }
}
