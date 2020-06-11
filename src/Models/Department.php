<?php

namespace Thans\Bpm\Models;

use Illuminate\Database\Eloquent\Model;
use Dcat\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\EloquentSortable\Sortable;

class Department extends Model implements Sortable
{
    use ModelTree;
    protected $table = 'form_departments';
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
