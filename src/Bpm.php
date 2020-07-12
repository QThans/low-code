<?php

namespace Thans\Bpm;

use Dcat\Admin\Admin;
use Dcat\Admin\Extension;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Thans\Bpm\Models\Apps;
use Thans\Bpm\Models\Department;
use Thans\Bpm\Models\DepartmentUsers;
use Thans\Bpm\Models\User;
use Dcat\Admin\Layout\Menu;
use Thans\Bpm\Http\Controllers\BpmController;
use Thans\Bpm\Models\Form;

class Bpm extends Extension
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

    // protected $lang = __DIR__.'/../resources/lang';

    // public $menu = [
    //     'title' => 'Bpm',
    //     'path'  => 'bpm',
    //     'icon'  => 'fa-cubes',
    // ];
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
}
