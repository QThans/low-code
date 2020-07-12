<?php

namespace Thans\Bpm\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Overtrue\LaravelVersionable\Versionable;

class FormComponents extends Model
{
    use HasDateTimeFormatter, Versionable, SoftDeletes;
    protected $casts = [
        'values' => 'json',
    ];
}
