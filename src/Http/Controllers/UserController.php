<?php

namespace Thans\Bpm\Http\Controllers;

use Dcat\Admin\Admin;
use Dcat\Admin\Controllers\UserController as BaseUserController;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Thans\Bpm\Models\User;
use Dcat\Admin\Show;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Widgets\Tree;
use Thans\Bpm\Models\Department;

class UserController extends BaseUserController
{
    protected function grid()
    {
        return Grid::make(User::with(['roles', 'departments']), function (Grid $grid) {
            $grid->id('ID')->sortable();
            $grid->username;
            $grid->name;

            if (config('admin.permission.enable')) {
                $grid->roles->pluck('name')->label('primary', 3);

                //                $permissionModel = config('admin.database.permissions_model');
                //                $roleModel = config('admin.database.roles_model');
                //                $nodes = (new $permissionModel())->allNodes();
                //                $grid->permissions
                //                    ->if(function () {
                //                        return !empty($this->roles);
                //                    })
                //                    ->showTreeInDialog(function (Grid\Displayers\DialogTree $tree) use (&$nodes, $roleModel) {
                //                        $tree->nodes($nodes);
                //
                //                        foreach (array_column($this->roles, 'slug') as $slug) {
                //                            if ($roleModel::isAdministrator($slug)) {
                //                                $tree->checkAll();
                //                            }
                //                        }
                //                    })
                //                    ->else()
                //                    ->emptyString();
            }
            $grid->column('departments', '所属部门')->pluck('name')->label('primary', 3);
            $grid->column('status', '状态')
                ->using(User::STATUS)
                ->dot(
                    [
                        1 => 'danger',
                        0 => 'success',
                    ],
                    'primary' // 默认颜色
                );

            $grid->created_at;
            $grid->updated_at->sortable();

            $grid->quickSearch(['id', 'name', 'username']);

            $grid->disableBatchDelete();
            $grid->showQuickEditButton();
            $grid->disableFilterButton();
            $grid->enableDialogCreate();
            $grid->withBorder();
            $grid->fixColumns(2, -1);
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                if ($actions->getKey() == User::DEFAULT_ID) {
                    $actions->disableDelete();
                }
                if ($actions->getKey() == User::DEFAULT_ID && User::DEFAULT_ID != Admin::user()->id) {
                    $actions->disableEdit();
                    $actions->disableQuickEdit();
                }
            });
        });
    }

    protected function detail($id)
    {
        return Show::make($id, User::with(['roles', 'departments']), function (Show $show) {
            $show->field('id');
            $show->field('username');
            $show->field('name');

            $show->field('avatar', __('admin.avatar'))->image();

            if (config('admin.permission.enable')) {
                $show->field('roles')->as(function ($roles) {
                    if (!$roles) {
                        return;
                    }

                    return collect($roles)->pluck('name');
                })->label();

                $show->field('departments', '所属部门')->as(function ($departments) {
                    if (!$departments) {
                        return;
                    }

                    return collect($departments)->pluck('name');
                })->label();

                $show->field('permissions')->unescape()->as(function () {
                    $roles = (array)$this->roles;

                    $permissionModel = config('admin.database.permissions_model');
                    $roleModel = config('admin.database.roles_model');
                    $permissionModel = new $permissionModel();
                    $nodes = $permissionModel->allNodes();

                    $tree = Tree::make($nodes);

                    $isAdministrator = false;
                    foreach (array_column($roles, 'slug') as $slug) {
                        if ($roleModel::isAdministrator($slug)) {
                            $tree->checkAll();
                            $isAdministrator = true;
                        }
                    }

                    if (!$isAdministrator) {
                        $keyName = $permissionModel->getKeyName();
                        $tree->check(
                            $roleModel::getPermissionId(array_column($roles, $keyName))->flatten()
                        );
                    }

                    return $tree->render();
                });
            }

            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    public function form()
    {
        return Form::make(User::with('roles'), function (Form $form) {
            $userTable = config('admin.database.users_table');

            $connection = config('admin.database.connection');

            $id = $form->getKey();

            $form->display('id', 'ID');

            $form->text('username', trans('admin.username'))
                ->required()
                ->creationRules(['required', "unique:{$connection}.{$userTable}"])
                ->updateRules(['required', "unique:{$connection}.{$userTable},username,$id"]);
            $form->text('name', trans('admin.name'))->required();

            $form->select('status', '状态')->options(User::STATUS)->required();

            $form->image('avatar', trans('admin.avatar'));

            if ($id) {
                $form->password('password', trans('admin.password'))
                    ->minLength(5)
                    ->maxLength(20)
                    ->customFormat(function () {
                        return '';
                    });
            } else {
                $form->password('password', trans('admin.password'))
                    ->required()
                    ->minLength(5)
                    ->maxLength(20);
            }

            $form->password('password_confirmation', trans('admin.password_confirmation'))->same('password');

            $form->ignore(['password_confirmation']);

            if (config('admin.permission.enable')) {
                $form->multipleSelect('roles', trans('admin.roles'))
                    ->options(function () {
                        $roleModel = config('admin.database.roles_model');

                        return $roleModel::all()->pluck('name', 'id');
                    })
                    ->customFormat(function ($v) {
                        return array_column($v, 'id');
                    });
            }

            $form->display('created_at', trans('admin.created_at'));
            $form->display('updated_at', trans('admin.updated_at'));

            if ($id == User::DEFAULT_ID) {
                $form->disableDeleteButton();
            }
        })->saving(function (Form $form) {
            if ($form->password && $form->model()->get('password') != $form->password) {
                $form->password = bcrypt($form->password);
            }

            if (!$form->password) {
                $form->deleteInput('password');
            }
        });
    }
}
