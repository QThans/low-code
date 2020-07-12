<?php

namespace Thans\Bpm\Http\Controllers;

use Dcat\Admin\Admin;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Form\NestedForm;
use Dcat\Admin\Grid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Thans\Bpm\Models\Apps;
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
            // $actions->append('<a href="/admin/bpm/form/builder/' . $actions->row->id . '/edit?type=auths"><i class="fa fa-edit"></i> 表单权限</a>');
            $actions->append('<a href="/admin/bpm/form/builder/' . $actions->row->id . '/edit?type=tables"><i class="fa fa-edit"></i> 数据表格</a>');
        });
        return $grid;
    }
    public function form()
    {
        return Form::make(new RepositoriesForm(['apps', 'events', 'components', 'tables']), function ($form) {
            // 判断是否是编辑页面
            Admin::style(<<<CSS
            .order{
                width:70px !important;
            }
CSS);
            $form->disableViewButton();
            $form->saving(function (Form $form) {
                $type = FacadesRequest::input('type', 'baseInfo');
                $form->type = 0;

                if (!$form->isEditing() || $type == 'baseInfo') {
                    $form->user_id = Admin::guard()->id();
                }
                if (!$form->isEditing() || $type == 'formDesign') {
                    $form->saving(function (Form $form) {
                        // CREATE UNIQUE INDEX submission_document_id_index_unique ON public.form_submissions USING btree (((submission -> 'documentId'::text)
                        //TODO：索引创建或删除，unique or index
                    });
                }
                if (!$form->isEditing() || $type == 'tables') {
                    $fields = $form->fields ? collect(array_values($form->fields))->filter(function ($value) {
                        return $value['_remove_'] == 1 ? false : true;
                    }) : '[]';
                    $filters = $form->filters ? collect(array_values($form->filters))->filter(function ($value) {
                        return $value['_remove_'] == 1 ? false : true;
                    }) : '[]';
                    $form->tables = ['code' => $form->code ?? '', 'fields' => $fields, 'filters' => $filters];
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
                ->add('数据表格', function (Form\StepForm $step) {
                    $this->tables($step);
                })
                ->done(function () use ($form) {
                    $resource = $form->getResource(0);
                    //添加到菜单、权限配置项

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
        $alias->rules(function ($form) {
            // 如果不是编辑状态，则添加字段唯一验证
            if (!$id = $form->model()->id) {
                return 'unique:forms,alias';
            }
        });
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
