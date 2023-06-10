<?php

namespace Thans\Bpm\Http\Controllers;

use Dcat\Admin\Admin;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\IFrameGrid;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Models\Menu;
use Dcat\Admin\Models\Permission;
use Thans\Bpm\Models\Repositories\Apps;
use Dcat\Admin\Show;
use Thans\Bpm\Models\Apps as ModelsApps;
use Thans\Bpm\Models\Department;

class AppsController extends AdminController
{
    protected function title()
    {
        return '应用';
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        if ($mini = request(IFrameGrid::QUERY_NAME)) {
            $grid = new IFrameGrid(new Apps());
        } else {
            $grid = new Grid(new Apps());
        }
        $grid->model()->orderBy('order');
        $grid->id('ID')->bold()->sortable();
        $grid->name('应用名字')->tree();
        $grid->icon;
        $grid->description('描述');
        $grid->order->orderable();
        if (!$mini) {
            $grid->created_at;
            $grid->updated_at->sortable();
        }
        $grid->disableBatchDelete();
        $grid->disableEditButton();
        $grid->showQuickEditButton();
        $grid->disableFilterButton();
        $grid->quickSearch(['id', 'name', 'icon', 'description']);
        $grid->enableDialogCreate();
        $grid->withBorder();
        $grid->fixColumns(2, -1);
        return $grid;
    }

    protected function detail($id)
    {
        return Show::make($id, new Apps(), function (Show $show) {
            $show->id;
            $show->field('parent.name', '父级应用');
            $show->name('应用');
            $show->icon;
            $show->description('描述');
            $show->order;
            $show->created_at;
            $show->updated_at;
        });
    }

    public function form()
    {
        return Form::make(new Apps(), function (Form $form) {
            $form->display('id', 'ID');
            $form->hidden('user_id');
            $form->select('parent_id', '父级应用')->default('0')->options(function () use ($form) {
                return ModelsApps::selectOptions();
            })->saving(function ($v) {
                return (int) $v;
            });
            $form->text('name', '应用名称')->required();
            $form->icon('icon', 'ICON')->required();
            $form->text('description', '描述');
            $form->number('order', '排序');
            $form->display('created_at', trans('admin.created_at'));
            $form->display('updated_at', trans('admin.updated_at'));
            $form->saving(function (Form $form) {
                if ($form->isCreating()) {
                    $form->user_id = Admin::guard()->id();
                }
            });
        });
    }
}
