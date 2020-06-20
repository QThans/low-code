<?php

namespace Thans\Bpm\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelVersionable\Versionable;
use Spatie\EloquentSortable\Sortable;

class FormAuthDepartment extends Model
{
    use HasDateTimeFormatter,Versionable;
    /**
     * 动作权限
     */
    const AUTH_ACTIONS = [
        'show' => '列表显示',
        'store' => '新增',
        'update' => '更新',
        'create' => '新增页面',
        'edit' => '编辑页面',
        'destroy' => '删除',
    ];
}
