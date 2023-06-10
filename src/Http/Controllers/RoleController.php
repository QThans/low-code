<?php

namespace Thans\Bpm\Http\Controllers;

use Dcat\Admin\Admin;
use Dcat\Admin\Controllers\RoleController as BaseRoleController;
use Dcat\Admin\Grid;
use Dcat\Admin\Auth\Permission;
use Dcat\Admin\Form;
use Dcat\Admin\IFrameGrid;
use Dcat\Admin\Models\Repositories\Role;
use Dcat\Admin\Models\Role as RoleModel;
use Dcat\Admin\Show;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Widgets\Tree;
use Thans\Bpm\Models\Role as ModelsRole;

class RoleController extends BaseRoleController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        if ($mini = request(IFrameGrid::QUERY_NAME)) {
            $grid = new IFrameGrid(new Role());
        } else {
            $grid = new Grid(new Role());
        }

        $grid->id('ID')->sortable();
        $grid->slug->label('primary');
        $grid->name;

        if (!$mini) {
            $grid->created_at;
            $grid->updated_at->sortable();
        }

        $grid->disableBatchDelete();
        $grid->disableEditButton();
        $grid->showQuickEditButton();
        $grid->disableFilterButton();
        $grid->quickSearch(['id', 'name', 'slug']);
        $grid->enableDialogCreate();
        $grid->withBorder();
        $grid->fixColumns(2, -1);
        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $roleModel = config('admin.database.roles_model');
            if ($roleModel::isAdministrator($actions->row->slug)) {
                $actions->disableDelete();
                $actions->disableQuickEdit();
            }
            if (!ModelsRole::isAdministrator($actions->row->slug)) {
                $actions->append('<a class="grid-row-edit" href="javascript:void(0);" data-url="/admin/data/roles/' . $actions->row->id . '" title="数据域"><i class="fa fa-group"></i></a>');
            }
        });

        return $grid;
    }
}
