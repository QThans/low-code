<?php

namespace Thans\Bpm\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Overtrue\LaravelVersionable\Versionable;

class FormTable extends Model
{
    use HasDateTimeFormatter, Versionable, SoftDeletes;
    protected $casts = [
        'fields' => 'array',
        'filters' => 'array',
        'title' => 'array',
    ];
    protected $fillable = ['fields', 'filters', 'code'];
}
