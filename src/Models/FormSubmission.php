<?php

namespace Thans\Bpm\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelVersionable\Versionable;

class FormSubmission extends Model
{
    use HasDateTimeFormatter, Versionable;

    protected $casts = [
        'submission' => 'array',
    ];

    public function setSubmissionAttribute($value)
    {
        $this->attributes['submission'] = json_encode($value);
    }
}
