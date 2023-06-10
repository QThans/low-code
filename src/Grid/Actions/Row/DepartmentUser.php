<?php

namespace Thans\Bpm\Grid\Actions\Row;

use Dcat\Admin\Grid\RowAction;

class DepartmentUser extends RowAction
{
    /**
     * 返回字段标题
     *
     * @return string
     */
    public function title()
    {
        return '<i class="fa fa-users"></i>';
    }

    /**
     * 添加JS
     *
     * @return string
     */
    protected function script()
    {
        return <<<JS

$('.grid-user-row').off('click');
$('.grid-user-row').on('click', function () {
    // layer.open({
    //     type: 2,
    //     title: '部门分配',
    //     area: ['80%', '80%'],
    //     content: '/admin/bpm/department/user?_resource_=1&department_id='+$(this).attr('data-id'),
    // });
    window.open('/admin/bpm/department/user?department_id='+$(this).attr('data-id'));
    return false;
});
JS;
    }

    public function html()
    {
        // 获取当前行数据ID
        $id = $this->getKey();

        // 获取当前行数据的用户名
        $username = $this->row->username;

        // 这里需要添加一个class, 和上面script方法对应
        $this->setHtmlAttribute(['data-id' => $id, 'class' => 'grid-user-row']);
        return parent::html();
    }
}
