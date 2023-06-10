<?php

namespace Thans\Bpm\Facades;

use Illuminate\Support\Facades\Facade;
use Thans\Bpm\Grid\Grid as GridGrid;

class Grid extends Facade
{
    /**
     * 获取组件的注册名称。
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return GridGrid::class;
    }
}
