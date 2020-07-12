<?php

namespace Thans\Bpm\Models;

use Dcat\Admin\Models\Role as DcatRole;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Overtrue\LaravelVersionable\Versionable;

class Role extends DcatRole
{
    use HasDateTimeFormatter, Versionable;
    
    protected $appends = ['submission'];

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
                'label' => '标识',
                'key' => 'slug',
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
