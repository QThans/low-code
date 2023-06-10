<?php

namespace Thans\Bpm\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Overtrue\LaravelVersionable\Versionable;

class FormEvent extends Model
{
    use HasDateTimeFormatter, Versionable, SoftDeletes;

    protected $fillable = ['type', 'name', 'event'];
    /**
     * 表单数据事件
     * 事件内容：PHP代码
     */
    const FORM_DATA_EVENTS = [
        'before_submit' => '提交前',
        'bofore_save' => '保存前',
        'after_save' => '保存后',
        'before_delete' => '删除前',
        'after_delete' => '删除后',
    ];
    /**
     * 表单页面事件
     * 事件内容：JS代码
     */
    const FROM_PAGE_EVENTS = [
        //页面显示发生事件
        'create_page' => '新增页面',
        'edit_page' => '编辑页面',
        'view_page' => '查看页面',
    ];
    /**
     * 事件类型
     */
    const EVENT_TYPE = [
        'FORM_DATA_EVENTS' => '数据事件',
        'FROM_PAGE_EVENTS' => '页面事件',
    ];
}
