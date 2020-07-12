<?php

namespace Thans\Bpm\Models;

use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Overtrue\LaravelVersionable\Versionable;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class Form extends Model  implements Sortable
{
    use HasDateTimeFormatter, Versionable, SortableTrait, SoftDeletes;

    protected $sortable = [
        // 设置排序字段名称
        'order_column_name' => 'order',
        // 是否在创建时自动排序，此参数建议设置为true
        'sort_when_creating' => true,
    ];
    // /**
    //  * 用户动作权限
    //  */
    // const USER_ACTIONS = [
    //     'show' => '列表显示',
    //     'store' => '新增',
    //     'update' => '更新',
    //     'create' => '新增页面',
    //     'edit' => '编辑页面',
    //     'destroy' => '删除',
    // ];
    // /**
    //  * 部门动作权限
    //  */
    // const DEPARTMENT_ACTIONS = [
    //     'show' => '列表显示',
    //     'store' => '新增',
    //     'update' => '更新',
    //     'create' => '新增页面',
    //     'edit' => '编辑页面',
    //     'destroy' => '删除',
    // ];
    public function apps()
    {
        return $this->belongsTo(Apps::class);
    }
    // public function departments()
    // {
    //     return $this->hasMany(FormDepartment::class);
    // }
    // public function users()
    // {
    //     return $this->hasMany(FormUser::class);
    // }

    public function events()
    {
        return $this->hasMany(FormEvent::class);
    }

    public function tables()
    {
        return $this->hasOne(FormTable::class);
    }
    public function components()
    {
        return $this->hasOne(FormComponents::class);
    }

    // public static function getByNoAuth($appsId)
    // {
    //     return self::with(['users', 'departments'])->where('apps_id', $appsId)->whereDoesntHave('departments')->whereDoesntHave('users')->get();
    // }
}
