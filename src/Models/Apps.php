<?php

namespace Thans\Bpm\Models;

use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelVersionable\Versionable;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class Apps extends Model implements Sortable
{
    use SortableTrait, Versionable, HasDateTimeFormatter;
    protected $table = 'apps';

    protected $sortable = [
        // 设置排序字段名称
        'order_column_name' => 'order',
        // 是否在创建时自动排序，此参数建议设置为true
        'sort_when_creating' => true,
    ];
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'apps_departments')->withTimestamps();
    }
    public function users()
    {
        return $this->belongsToMany(Administrator::class, 'apps_users', 'apps_id', 'user_id')->withTimestamps();
    }
}
