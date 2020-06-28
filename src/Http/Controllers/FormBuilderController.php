<?php

namespace Thans\Bpm\Http\Controllers;

use Dcat\Admin\Admin;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Form\NestedForm;
use Dcat\Admin\Grid;
use Dcat\Admin\Models\Administrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Thans\Bpm\Grid\Actions\Row\FormBuilder\EditButton;
use Thans\Bpm\Models\Apps;
use Thans\Bpm\Models\Department;
use Thans\Bpm\Models\Form as ModelsForm;
use Thans\Bpm\Models\FormEvent;
use Thans\Bpm\Models\Repositories\Form as RepositoriesForm;

class FormBuilderController extends AdminController
{
    /**
     * @var string
     */
    protected $title = '表单列表';

    protected $description = [
        'index'  => '',
        'show'   => '',
        'edit'   => '',
        'create' => '创建',
    ];

    //表单构建
    public function grid()
    {
        $grid = new Grid(new RepositoriesForm(['apps']));
        $grid->model()->orderBy('order');
        $grid->id('ID')->bold()->sortable();
        $grid->name('表单名称');
        $grid->alias('标识');
        $grid->column('apps.name', '所属应用');
        $grid->description('描述');
        $grid->order->orderable();
        $grid->disableBatchDelete();
        $grid->disableEditButton();
        $grid->disableViewButton();
        $grid->filter(function ($filter) {
            $filter->in('apps_id', '所属应用')->multipleSelect(Apps::all()->pluck('name', 'id')->toArray());
        });
        $grid->quickSearch(['id', 'name']);
        $grid->actions(function (Grid\Displayers\Actions $actions) {
            // prepend一个操作
            $actions->append('<a href="/admin/bpm/form/builder/' . $actions->row->id . '/edit?type=baseInfo"><i class="fa fa-edit"></i> 基本信息</a>');
            $actions->append('<a href="/admin/bpm/form/builder/' . $actions->row->id . '/edit?type=formDesign"><i class="fa fa-edit"></i> 表单设计</a>');
            $actions->append('<a href="/admin/bpm/form/builder/' . $actions->row->id . '/edit?type=events"><i class="fa fa-edit"></i> 事件配置</a>');
            $actions->append('<a href="/admin/bpm/form/builder/' . $actions->row->id . '/edit?type=auths"><i class="fa fa-edit"></i> 表单权限</a>');
            $actions->append('<a href="/admin/bpm/form/builder/' . $actions->row->id . '/edit?type=tables"><i class="fa fa-edit"></i> 数据表格</a>');
        });
        return $grid;
    }
    public function form()
    {
        return Form::make(new RepositoriesForm(['apps', 'departments', 'users', 'events', 'components', 'tables']), function ($form) {
            // 判断是否是编辑页面
            Admin::style(<<<CSS
            .order{
                width:70px !important;
            }
CSS);
            $form->saving(function (Form $form) {
                $type = FacadesRequest::input('type', 'baseInfo');
                $form->type = 0;

                if (!$form->isEditing() || $type == 'baseInfo') {
                    $form->user_id = Admin::guard()->id();
                }
                if (!$form->isEditing() || $type == 'tables') {
                    $form->tables = ['code' => $form->code ?? '', 'fields' => $form->fields ? array_values($form->fields) : '[]', 'filters' => $form->filters ? array_values($form->filters) : '[]'];
                    $form->deleteInput('code');
                    $form->deleteInput('fields');
                    $form->deleteInput('filters');
                }
            });
            if ($form->isEditing()) {
                $type = FacadesRequest::input('type', 'baseInfo');
                $this->$type($form, true);
                $form->action(route('builder.update', ['builder' => $form->builder()->getResourceId(), 'type' => $type]));
                return;
            }
            $form->multipleSteps()
                ->width('100%')
                ->remember(true)
                ->add('基础信息', function (Form\StepForm $step) {
                    $this->baseInfo($step);
                })
                ->add('表单设计', function (Form\StepForm $step) {
                    $this->formDesign($step);
                })
                ->add('事件配置', function (Form\StepForm $step) {
                    $this->events($step);
                })
                ->add('表单权限', function (Form\StepForm $step) use ($form) {
                    $this->auths($step);
                })
                ->add('数据表格', function (Form\StepForm $step) {
                    $this->tables($step);
                })
                ->done(function () use ($form) {
                    $resource = $form->getResource(0);
                    $data = [
                        'title'       => '操作成功',
                        'description' => '表单：' . $form->name . '，创建成功',
                        'createUrl'   => action([FormBuilderController::class, 'create']),
                        'backUrl'     => $resource,
                    ];
                    return view('admin::form.done-step', $data);
                });
        });
    }
    public function baseInfo($form, $isEditing = false)
    {
        $form->text('name', '表单名称')->required(true);
        $form->hidden('user_id');
        $alias = $form->text('alias', '标识')->rules('required|regex:/[_a-zA-Z0-9]/|min:4|max:50')->attribute('min', 4)->attribute('max', 50)->required(true);
        if ($isEditing) {
            $alias->disable();
        }
        $form->select('apps_id', '所属应用')->options(Apps::all()->pluck('name', 'id'))->required(true);
        $form->textarea('description', '描述');
    }
    public function formDesign($form, $isEditing = false)
    {
        $formBuilder = $form->bpmFormBuilder('components.values')->saving(function ($value) {
            return is_array($value) ? $value : json_decode($value, true);
        });
        if ($isEditing) {
            $formBuilder->value($form->model()->components['values']);
        } else {
            $formId = 'form_' . md5($formBuilder->getElementId());
            $form->shown(<<<JS
    var formArray = args.formArray[args.formArray.length-1];
    Formio.icons = "fontawesome"
    var {$formId} = Formio.builder(document.getElementById('{$formBuilder->getElementId()}'), JSON.parse(formArray['value']), {
        language: 'zh-CN',
        noDefaultSubmitButton: true,
        i18n: cn,
    }).then(function (form) {
    form.on('change', function(build) {
        $('input[name="{$formBuilder->getElementName()}"]').val(JSON.stringify(form.schema));
    });
});
JS);
        }
    }
    public function events($form, $isEditing = false)
    {
        $form->hasMany('events', '事件列表', function (NestedForm $table) {
            $table->select('type', '事件类型')->options(FormEvent::EVENT_TYPE)->load('name', route('bpm.formEvents'));
            $table->select('name', '事件名称');
            $table->textarea('event', '事件代码');
        })->useTable();
    }
    public function auths($form, $isEditing = false)
    {
        $form->hasMany('departments', '部门授权', function (NestedForm $table) {
            $table->multipleSelect('department_id', '部门选择')->options(Department::selectOptions());
            $table->multipleSelect('actions', '事件名称')->options(ModelsForm::DEPARTMENT_ACTIONS)->help('为空默认为所有');
        })->useTable()->saving(function ($value) {
            $depatment = [];
            foreach ($value as $key => $val) {
                if (!isset($val['department_id'])) {
                    continue;
                }
                foreach ($val['department_id'] as $k => $v) {
                    $depatment[] = [
                        'department_id' => $v,
                        'actions' => isset($val['actions']) ? implode(',', $val['actions']) : '*',
                        'id' => $val['id'],
                        '_remove_' => $val['_remove_'],
                    ];
                }
            }
            return $depatment;
        });
        $form->hasMany('users', '用户授权', function (NestedForm $table) {
            $table->multipleSelect('user_id', '用户选择')->options(Administrator::all()->pluck('name', 'id')->toArray());
            $table->multipleSelect('actions', '事件名称')->options(ModelsForm::DEPARTMENT_ACTIONS)->help('为空默认为所有');
        })->useTable()->saving(function ($value) {
            $users = [];
            foreach ($value as $key => $val) {
                if (!isset($val['user_id'])) {
                    continue;
                }
                foreach ($val['user_id'] as $k => $v) {
                    $users[] = [
                        'user_id' => $v,
                        'actions' => isset($val['actions']) ? implode(',', $val['actions']) : '*',
                        'id' => $val['id'],
                        '_remove_' => $val['_remove_'],
                    ];
                }
            }
            return $users;
        });
    }
    public function tables($form, $isEditing = false)
    {
        $fields = $form->table('fields', '数据表格', function (NestedForm $table) {
            $table->text('label', '字段名称');
            $table->text('name', '字段标识');
            $table->number('order', '排序');
        });
        $filters = $form->table('filters', '筛选字段', function (NestedForm $table) {
            $table->text('label', '字段名称');
            $table->text('name', '字段标识');
            $table->textarea('options', '配置');
            $table->number('order', '排序');
        });
        if ($isEditing) {
            $fields->value($form->model()->tables['fields']);
            $filters->value($form->model()->tables['filters']);
        }
        $form->textarea('code', '数据处理');
        $form->hidden('tables')->customFormat(function ($value) {
            return '';
        });
    }
    /**
     * 根据类型获取事件列表
     * @param mixed $type 
     * @return void 
     */
    public function formEvents(Request $request)
    {
        $key = $request->get('q');
        return collect(constant("Thans\Bpm\Models\FormEvent::$key"))->map(function ($val, $key) {
            return  [
                'id' => $key,
                'text' => $val
            ];
        });
    }
}
