<?php

namespace Thans\Bpm\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Overtrue\LaravelVersionable\Versionable;

class UserPlatformAuth extends Model
{
    protected $table = 'admin_users_platform_auths';
    use HasDateTimeFormatter, Versionable, SoftDeletes;
    protected $casts = [
        'detail' => 'array',
    ];
    protected $fillable = ['user_id', 'oauth_name', 'unionid', 'openid', 'detail'];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
