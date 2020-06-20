<?php

namespace Thans\Bpm\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelVersionable\Versionable;

class Form extends Model
{
    use HasDateTimeFormatter,Versionable;
    public function apps()
    {
        return $this->hasOne(Apps::class);
    }
}
