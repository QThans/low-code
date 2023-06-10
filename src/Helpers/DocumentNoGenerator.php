<?php

namespace Thans\Bpm\Helpers;

use Illuminate\Support\Facades\Redis;

class DocumentNoGenerator
{
    public static function generate($key, $format, $prefix = '', $suffix = '')
    {
        return $prefix . date($format) . str_pad(self::incrementId($key . '_' . date($format)), 5, '0', STR_PAD_LEFT) . $suffix;
    }
    public static function incrementId($key = 'model-primary-key')
    {
        return Redis::incr($key);
    }
}
