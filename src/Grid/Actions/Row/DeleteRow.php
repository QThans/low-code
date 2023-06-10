<?php

namespace Thans\Bpm\Grid\Actions\Row;

use Dcat\Admin\Grid\RowAction;

class DeleteRow extends RowAction
{
    protected $deleteTips = '';

    public function __construct($deleteTips = '', $title = null)
    {
        parent::__construct($title);
        $this->deleteTips = $deleteTips;
    }
    /**
     * 返回字段标题
     * 
     * @return string
     */
    public function title()
    {
        //     <a title="删除" href="javascript:void(0);" data-message="ID - $id' . ($this->deleteTips ? '，' . $this->deleteTips : '') . '" data-url="http://bpm.bt/admin/bpm/MCPZ/form/767" data-action="delete">

        // </a>
        return '<i class="feather icon-trash grid-action-icon"></i>';
    }

    public function html()
    {
        // 获取当前行数据ID
        $id = $this->getKey();

        // 这里需要添加一个class, 和上面script方法对应
        $this->setHtmlAttribute([
            'data-id' => $id,
            'data-action' => 'delete',
            'data-message' => 'ID - ' . $id . ($this->deleteTips ? '，' . $this->deleteTips : ''),
            'title' => __('admin.delete'),
            'data-url'     => $this->url(),
        ]);

        return parent::html();
    }

    public function url()
    {
        return "{$this->resource()}/{$this->getKey()}";
    }
}
