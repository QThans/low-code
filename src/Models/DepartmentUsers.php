<?php

namespace Thans\Bpm\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelVersionable\Versionable;

class DepartmentUsers extends Model
{
    use HasDateTimeFormatter, Versionable;
    public $incrementing = true;
    protected $fillable = ['user_id','department_id'];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }
}
