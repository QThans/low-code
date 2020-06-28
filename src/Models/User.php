<?php

namespace Thans\Bpm\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelVersionable\Versionable;

class User extends Model
{
    use HasDateTimeFormatter, Versionable;
    protected $table = 'admin_users';
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_users', 'user_id', 'department_id')->withPivot('updated_at')->withTimestamps();
    }
}
