<?php

namespace Thans\Bpm\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Overtrue\LaravelVersionable\Versionable;

class FormSubmission extends Model
{
    use HasDateTimeFormatter, Versionable, SoftDeletes;


    protected $casts = [
        'submission' => 'array',
    ];

    protected $fillable = [
        'header',
    ];


    protected $hidden = [
        'header',
    ];

    protected $alias = '';

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
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function updatedUser()
    {
        return $this->belongsTo(User::class, 'updated_user_id', 'id');
    }
}
