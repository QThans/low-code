<?php

namespace Thans\Bpm\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelVersionable\Versionable;

class FormComponents extends Model
{
    use HasDateTimeFormatter, Versionable;
    protected $casts = [
        'values' => 'json',
    ];
}
