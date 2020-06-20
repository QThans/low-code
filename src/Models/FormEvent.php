<?php

namespace Thans\Bpm\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelVersionable\Versionable;

class FormEvent extends Model
{
    use HasDateTimeFormatter, Versionable;
    /**
     * 表单数据事件
     * 事件内容：PHP代码
     */
    const FORM_DATA_EVENTS = [
        'after_read' => '查询后',
        'before_insert' => '新增前',
        'after_insert' => '新增后',
        'before_update' => '更新前',
        'after_update' => '更新后',
        'before_write' => '写入前',
        'after_write' => '写入后',
        'before_delete' => '删除前',
        'after_delete' => '删除后',
    ];
    /**
     * 表单页面事件
     * 事件内容：JS代码
     */
    const FROM_PAGE_EVENTS = [
        //页面显示发生事件
        'after_show' => '页面显示后',
        'after_create' => '新增表单显示后',
        'before_store' => '新增提交前',
        'after_store' => '新增提交后',
        'after_edit' => '编辑表单显示后',
        'before_update' => '更新提交前',
        'after_update' => '更新提交后',
    ];
    /**
     * 事件类型
     */
    const EVENT_TYPE = [
        'FORM_DATA_EVENTS' => '数据事件',
        'FROM_PAGE_EVENTS' => '页面事件',
    ];
}
