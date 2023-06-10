<?php

namespace Thans\Bpm\Models;

use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Dcat\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Overtrue\LaravelVersionable\Versionable;
use Spatie\EloquentSortable\Sortable;

class Apps extends Model implements Sortable
{
    use Versionable, ModelTree, HasDateTimeFormatter, SoftDeletes;
    protected $table = 'apps';
    protected $titleColumn = 'name';
    protected $parentColumn = 'parent_id';
    protected $orderColumn = 'order';
    
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'apps_departments')->withTimestamps();
    }
    public function users()
    {
        return $this->belongsToMany(Administrator::class, 'apps_users', 'apps_id', 'user_id')->withTimestamps();
    }
    public static function getByDepartments($departments)
    {
        return self::with('departments')->whereHas('departments', function ($query) use ($departments) {
            return $query->whereIn('department_id', $departments);
        })->get();
    }
    public static function getByUserIds($userId)
    {
        return self::with('users')->whereHas('users', function ($query) use ($userId) {
            return $query->whereIn('user_id', $userId);
        })->get();
    }
    public static function getByUserId($userId)
    {
        return self::getByUserIds([$userId]);
    }
    public static function getByNoDepartment()
    {
        return  self::with('departments')->whereDoesntHave('departments')->get();
    }
    /**
     * Get Parent Apps
     * @return BelongsTo 
     */
    public function parent()
    {
        return $this->hasOne(Apps::class, 'id', 'parent_id');
    }
}
