<?php

namespace Thans\Bpm\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Overtrue\LaravelVersionable\Versionable;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class FormSubmission extends Model implements Sortable
{
    const DEFAULT_STATUS = 10000;
    const INVALID_STATUS = 40000;

    const INVALID_STATUS_TITLE = '无效';

    const INVALID_STATUS_ICON = 'fa-minus-circle';

    const SAVE_STATUS = 10001;

    const SAVE_STATUS_TITLE = '暂存';

    const SAVE_STATUS_ICON = 'fa-edit';

    const DEFAULT_STATUS_TITLE = '正常';

    const DEFAULT_STATUS_ICON = 'fa-circle-thin';

    const USER_ID_FIELD_KEY = '____user_id';

    use HasDateTimeFormatter, Versionable, SoftDeletes, SortableTrait;

    protected $status = [
        FormSubmission::DEFAULT_STATUS => FormSubmission::DEFAULT_STATUS_TITLE,
        FormSubmission::SAVE_STATUS => FormSubmission::SAVE_STATUS_TITLE,
        FormSubmission::INVALID_STATUS => FormSubmission::INVALID_STATUS_TITLE,
    ];

    protected $sortable = [
        // 设置排序字段名称
        'order_column_name' => 'updated_at',
        // 是否在创建时自动排序，此参数建议设置为true
        'sort_when_creating' => true,
    ];

    protected $casts = [
        'submission' => 'array',
    ];

    protected $fillable = [
        'form_id',
        'form_alias',
        'submission',
        'user_id',
        'updated_user_id',
        'created_user_id',
        'header',
        'status',
    ];


    protected $hidden = [
        'header',
    ];

    protected $alias = '';

    public function getStatusTitle($status)
    {
        return $this->status[$status];
    }

    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this->setTable('form_submissions')
            ->from('form_submissions as ' . $this->alias);
    }

    public function getDeletedAtColumn()
    {
        return $this->alias ? $this->alias . '.' . 'deleted_at' : 'deleted_at';
    }

    public function setSubmissionAttribute($value)
    {
        $this->attributes['submission'] = json_encode($value);
    }

    public function setUpdatedAtAttribute($value)
    {
        $this->attributes['updated_at'] = date('Y-m-d H:i:s');
    }
    public function createdUser()
    {
        return $this->belongsTo(User::class, 'created_user_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function updatedUser()
    {
        return $this->belongsTo(User::class, 'updated_user_id', 'id');
    }
}
