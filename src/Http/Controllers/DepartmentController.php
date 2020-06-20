<?php

namespace Thans\Bpm\Http\Controllers;

use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\IFrameGrid;
use Thans\Bpm\Models\Repositories\Department;
use Dcat\Admin\Show;
use Thans\Bpm\Models\Department as ModelsDepartment;

class DepartmentController extends AdminController
{
    protected function title()
    {
        return '部门';
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        if ($mini = request(IFrameGrid::QUERY_NAME)) {
            $grid = new IFrameGrid(new Department());
        } else {
            $grid = new Grid(new Department());
        }
        $grid->id('ID')->bold()->sortable();
        $grid->name->tree(true); // 开启树状表格功能 
        $grid->order->orderable();
        $grid->order;

        if (!$mini) {
            $grid->created_at;
            $grid->updated_at->sortable();
        }

        $grid->disableBatchDelete();
        $grid->disableEditButton();
        $grid->showQuickEditButton();
        $grid->disableFilterButton();
        $grid->quickSearch(['id', 'name']);
        $grid->enableDialogCreate();
        return $grid;
    }

    protected function detail($id)
    {
        $model = ModelsDepartment::with('parent');
        return Show::make($id, $model, function (Show $show) {
            $show->id;
            $show->field('parent.name', '上级部门');
            $show->name('部门名称');
            $show->order;
            $show->created_at;
            $show->updated_at;
        });
    }

    public function form()
    {
        return Form::make(new Department(), function (Form $form) {

            $form->display('id', 'ID');
            $form->select('parent_id', '父级部门')->default('0')->options(function () {
                return ModelsDepartment::selectOptions();
            })->saving(function ($v) {
                return (int) $v;
            });
            $form->text('name', '部门名称')->required();

            $form->number('order', '排序');

            $form->display('created_at', trans('admin.created_at'));
            $form->display('updated_at', trans('admin.updated_at'));
        });
    }
}
