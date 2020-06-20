<?php

namespace Thans\Bpm\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Dcat\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Overtrue\LaravelVersionable\Versionable;
use Spatie\EloquentSortable\Sortable;

class Department extends Model implements Sortable
{
    use HasDateTimeFormatter, ModelTree, Versionable;
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
}
