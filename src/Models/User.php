<?php

namespace Thans\Bpm\Models;

use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Overtrue\LaravelVersionable\Versionable;

class User extends Administrator
{
    use HasDateTimeFormatter, Versionable;

    protected $table = 'admin_users';

    protected $appends = ['submission'];

    protected $hidden = ['password', 'remember_token'];

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_users', 'user_id', 'department_id')->withPivot('updated_at')->withTimestamps();
    }

    public function getSubmissionAttribute()
    {
        $submission = [];
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->hidden)) {
                $submission[$key] = $value;
            }
        }
        return $submission;
    }

    public static function components()
    {
        $components['components']['values']['components'] = [
            [
                'label' => '用户名',
                'key' => 'username',
                'input' => true
            ],
            [
                'label' => '名称',
                'key' => 'name',
                'input' => true
            ]
        ];
        return $components;
    }
}
