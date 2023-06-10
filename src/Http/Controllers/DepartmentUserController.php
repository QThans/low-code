<?php

namespace Thans\Bpm\Http\Controllers;

use Dcat\Admin\Admin;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\IFrameGrid;
use Illuminate\Support\Facades\Request;
use Thans\Bpm\Models\Department as ModelsDepartment;
use Thans\Bpm\Models\DepartmentUsers;
use Thans\Bpm\Models\User;

class DepartmentUserController extends AdminController
{
    protected function title()
    {
        return '部门所属用户';
    }

    protected function IFrameGrid()
    {
        return $this->grid();
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $model = new DepartmentUsers();
        $departmentIds = ModelsDepartment::childrenIds()[Request::input('department_id')];
        $model = $model::with(['department', 'user'])->whereIn('department_id', $departmentIds);
        if ($mini = request(IFrameGrid::QUERY_NAME)) {
            $grid = new IFrameGrid($model);
        } else {
            $grid = new Grid($model);
        }
        $grid->setActionClass(\Dcat\Admin\Grid\Displayers\Actions::class);
        $grid->id('ID')->bold()->sortable();
        $grid->column('user.username', '用户名');
        $grid->column('user.name', '名称');
        $grid->column('department.name', '部门')->label();
        $grid->column('updated_at', '加入时间')->label();
        $grid->showActions();
        $grid->model()->setConstraints(['department_id' => Request::input('department_id')]);
        $grid->disableBatchDelete();
        $grid->disableEditButton();
        $grid->disableViewButton();
        $grid->showQuickEditButton();
        $grid->disableFilterButton();
        $grid->quickSearch(['id', 'name']);
        $grid->enableDialogCreate();
        $grid->showCreateButton();
        $grid->disableEditButton();
        return $grid;
    }

    protected function detail($id)
    {
    }

    public function form()
    {
        return Form::make(new DepartmentUsers(), function (Form $form) {
            $departmentId = Request::input('department_id');
            $form->display('id', 'ID');
            $form->select('department_id', '部门选择')->default('0')->options(function () {
                return ModelsDepartment::selectOptions();
            })->saving(function ($v) {
                return (int) $v;
            })->required(true)->value($departmentId);
            if ($form->isEditing()) {
                $form->select('user_id', '用户选择')->options(User::all()->pluck('username', 'id')->toArray());
            } else {
                $form->multipleSelect('user_id', '用户选择')->options(User::all()->pluck('username', 'id')->toArray());
            }
            // 判断是否是新增操作
            $form->saving(function ($form) {
                if ($form->isCreating()) {
                    foreach (array_filter($form->user_id) as $key => $value) {
                        $data = [
                            'user_id' => $value,
                            'department_id' => $form->department_id
                        ];
                        DepartmentUsers::firstOrCreate($data);
                    }
                    return $form->success('保存成功');
                }
                if ($form->isEditing()) {
                    $data = [
                        'user_id' => $form->user_id,
                        'department_id' => $form->department_id
                    ];
                    DepartmentUsers::where('id', $form->model()->id)->update($data);
                    return $form->success('保存成功');
                }
            });
            $form->display('created_at', trans('admin.created_at'));
            $form->display('updated_at', trans('admin.updated_at'));
        });
    }
}
