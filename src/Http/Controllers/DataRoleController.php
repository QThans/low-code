<?php

namespace Thans\Bpm\Http\Controllers;

use Dcat\Admin\Form;
use Dcat\Admin\Layout\Content;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Thans\Bpm\Models\DataRole;
use Thans\Bpm\Models\Form as ModelsForm;
use Thans\Bpm\Models\Role;
use Thans\Bpm\Helpers\Component as HelperComponent;
use Thans\Bpm\Models\Form as ModelForm;

class DataRoleController
{
    public function index(Content $content, $role_id)
    {
        $role = Role::find($role_id);
        return $content
            ->header('数据域')
            ->description('数据访问范围')
            ->body($this->form($role->name, $role->id));
    }

    public function store()
    {
        $ranges = request()->input('range');
        $fields = request()->input('fields');
        $role_id = request()->input('role_id');
        $data = [];
        DataRole::where('role_id', $role_id)->delete();
        foreach ($ranges as $key => $value) {
            $data[] = [
                'role_id' => $role_id,
                'form_id' => $key,
                'fields' => isset($fields[$key]) && $fields[$key] ? json_encode($fields[$key]) : '',
                'range' => $value
            ];
        }
        DB::table('data_roles')->insert($data);
        return $this->form()->success('配置成功', '/data/roles/' . $role_id);
    }


    public function form($role = '', $id = '')
    {
        return Form::make(new DataRole(), function (Form $form) use ($role, $id) {
            $form->disableListButton();
            $form->disableCreatingCheck();
            $form->disableViewButton();
            $form->disableViewCheck();
            $form->disableEditingCheck();
            $form->title($role . ' 数据域配置');
            $forms = ModelsForm::orderBy('id', 'desc')->get();

            $form->column(4, function (Form $form) {
                $form->html('应用名称');
            });
            $form->column(4, function (Form $form) {
                $form->html('权限范围');
            });
            $form->column(4, function (Form $form) {
                $form->html('自定义验证字段');
            });
            $form->column(12, function (Form $form) use ($id) {
                $form->hidden('role_id')->value($id);
            });
            $ranges = DataRole::where('role_id', $id)->pluck('range', 'form_id');
            $fields = DataRole::where('role_id', $id)->pluck('fields', 'form_id');
            foreach ($forms as $key => $value) {
                $form->column(4, function (Form $form) use ($value) {
                    $form->text('', '')->value($value['name'])->disable()->prepend('');
                });
                $form->column(4, function (Form $form) use ($value, $ranges) {
                    $form->radio('range[' . $value['id'] . ']', '')->options(['self' => '自己', 'section' => '部门', 'all' => '全部'])->value(isset($ranges[$value['id']]) ? $ranges[$value['id']] : 'self');
                });
                $form->column(4, function (Form $form) use ($value, $fields) {
                    $components = ModelForm::with(['apps', 'events', 'components'])->where('id', $value['id'])->first();
                    $options = [];
                    if (isset($components['components']->values['components'])) {
                        $components = $components['components']->values['components'];
                        HelperComponent::setFields([]);
                        $fieldsComponent = HelperComponent::eachComponents($components);
                        foreach ($fieldsComponent as $fc) {
                            if (isset($fc['data']['resource']) && $fc['data']['resource'] == 'users') {
                                $options[$fc['key']] = $fc['label'];
                            }
                        }
                    }
                    $form->multipleSelect('fields[' . $value['id'] . ']', '')->options($options)->value(isset($fields[$value['id']]) ? json_decode($fields[$value['id']], true) : '');
                });
            }
            $indexUrl = '/admin/auth/roles';
            $form->tools(function (Form\Tools $tools) use ($indexUrl) {
                $tools->prepend('<a href="' . $indexUrl . '" class="btn btn-sm btn-white "><i class="feather icon-list"></i><span class="d-none d-sm-inline">&nbsp;角色组</span></a>');
            });
            $form->width(12, 0);
        });
    }
}
