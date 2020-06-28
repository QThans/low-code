<?php

namespace Thans\Bpm\Models;

use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Dcat\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Overtrue\LaravelVersionable\Versionable;
use Spatie\EloquentSortable\Sortable;
use Thans\Bpm\Traits\ChildrenManage;

class Department extends Model implements Sortable
{
    use HasDateTimeFormatter, ModelTree, Versionable, ChildrenManage;
    protected $table = 'departments';
    protected $titleColumn = 'name';
    protected $parentColumn = 'parent_id';
    protected $orderColumn = 'order';
    /**
     * 获取父级部门
     * @return BelongsTo 
     */
    public function parent()
    {
        return $this->hasOne(Department::class, 'id', 'parent_id');
    }
    public function users()
    {
        return $this->belongsToMany(Administrator::class, 'department_users', 'department_id', 'user_id');
    }
    public static function getByUserId($userId)
    {
        return self::with('users')->whereHas('users', function ($query) use ($userId) {
            return $query->where('user_id', $userId);
        })->get();
    }
}
