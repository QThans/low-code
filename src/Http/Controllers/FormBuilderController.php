<?php

namespace Thans\Bpm\Http\Controllers;

use Dcat\Admin\Admin;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Form\NestedForm;
use Dcat\Admin\Grid;
use Dcat\Admin\Models\Menu;
use Dcat\Admin\Models\Permission;
use Thans\Bpm\Models\Form as ModelsForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Thans\Bpm\Helpers\Component as HelperComponent;
use Thans\Bpm\Bpm;
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

    protected $formSubmissionFields = [
        'id' => ['label' => 'ID'],
        'createdUser.id' => ['label' => '创建账号ID'],
        'createdUser.name' => ['label' => '创建账号名称'],
        'createdUser.username' => ['label' => '创建账号用户名'],
        'user.id' => ['label' => '所属账号ID'],
        'user.name' => ['label' => '所属账号名称'],
        'user.username' => ['label' => '所属账号用户名'],
        'updateduser.id' => ['label' => '更新账号ID'],
        'updateduser.name' => ['label' => '更新账号名称'],
        'updateduser.username' => ['label' => '更新账号用户名'],
        'created_at' => ['label' => '创建时间'],
        'updated_at' => ['label' => '修改时间'],
    ];

    protected $indexSql = [];

    protected $uniqueSql = [];

    protected $needDelIndex = [];

    protected $needDelUnique = [];

    //表单构建
    public function grid()
    {
        $grid = new Grid(new RepositoriesForm(['apps']));
        $grid->model()->orderBy('order');
        $grid->id('ID')->bold()->sortable();
        $grid->name('表单名称');
        $grid->alias('标识');
        // $grid->column('apps.save_tips', '保存提示');
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
            $actions->append('<a style="margin-right: 5px;" href="/admin/bpm/forms/' . $actions->row->id . '/edit?type=baseInfo"><i class="fa fa-edit"></i> 基本信息 </a>');
            $actions->append('<a style="margin-right: 5px;" href="/admin/bpm/forms/' . $actions->row->id . '/edit?type=formDesign"><i class="fa fa-edit"></i> 表单设计 </a>');
            $actions->append('<a style="margin-right: 5px;" href="/admin/bpm/forms/' . $actions->row->id . '/edit?type=events"><i class="fa fa-edit"></i> 事件配置 </a>');
            $actions->append('<a style="margin-right: 5px;" href="/admin/bpm/forms/' . $actions->row->id . '/edit?type=tables"><i class="fa fa-edit"></i> 数据表格 </a>');
        });
        $grid->withBorder();
        $grid->fixColumns(2, -1);
        Admin::style(<<<CSS
.flatpickr-calendar{
    display:none;
}
CSS);
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
            .formio-component-modal-wrapper-container .formio-dialog.formio-dialog-theme-default .formio-dialog-content{
                width:50%;
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
                    if (isset($form->components['values'])) {
                        $components = json_decode($form->components['values'], true);
                        if (isset($components['components'])) {
                            //索引和唯一索引仅支持第一层次组件
                            foreach ($components['components'] as $key => $value) {
                                if (isset($value['dbIndex']) && $value['dbIndex']) {
                                    //索引
                                    $this->indexSql[$value['key']] = "CREATE INDEX IF NOT EXISTS index_{index_name} ON form_submissions USING GIN (((submission -> '" . $value['key'] . "'::text))) WHERE form_id = {form_id} AND deleted_at IS NULL;";
                                } else {
                                    $this->needDelIndex[] = $value['key'];
                                }
                                if (isset($value['validate']) && isset($value['validate']['unique']) && $value['validate']['unique']) {
                                    $this->uniqueSql[$value['key']] = "CREATE UNIQUE INDEX IF NOT EXISTS unique_{index_name} ON form_submissions USING btree (((submission -> '" . $value['key'] . "'::text))) WHERE form_id = {form_id} AND deleted_at IS NULL;";
                                } else {
                                    $this->needDelUnique[] = $value['key'];
                                }
                            }
                        }
                    }
                }
                if ($form->isEditing() && $type == 'formDesign') {
                    $this->deleteIndexAndUnique($form->model()->alias, $form->model()->id);
                    $msg = $this->executeIndexAndUnique($form->model()->alias, $form->model()->id, $form);
                    if ($msg) {
                        return $form->error($msg);
                    }
                }
                if (!$form->isEditing() || $type == 'tables') {
                    $form->tables = ['code' => $form->code ?? '', 'fields' => $form->data['fields'], 'filters' => $form->data['filters'], 'title' => isset($form->data['title']) ? $form->data['title'] : ''];
                    $form->deleteInput('title');
                    $form->deleteInput('code');
                    $form->deleteInput('data');
                }
            });
            if ($form->isEditing()) {
                $type = FacadesRequest::input('type', 'baseInfo');
                $this->$type($form, true);
                $form->action(route('forms.update', ['form' => $form->builder()->getResourceId(), 'type' => $type]));
                return;
            }

            $form->deleted(function (Form $form, $result) {
                $data = $form->model()->toArray();
                if (!$result) {
                    return $this->error('数据删除失败');
                }
                //删除索引
                foreach ($data as $key => $value) {
                    if (isset($value['components']['values']['components'])) {
                        //索引和唯一索引仅支持第一层次组件
                        foreach ($value['components']['values']['components'] as $component) {
                            $this->needDelIndex[] = $component['key'];
                            $this->needDelUnique[] = $component['key'];
                        }
                    }
                    $this->deleteIndexAndUnique($value['alias'], $value['id']);
                }
                return $form->success('删除成功');
            });

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
                    $step->leaving(<<<JS
                    Dcat.reload();
JS);
                })
                ->add('数据表格', function (Form\StepForm $step) {
                    $this->tables($step);
                })
                ->done(function ($done) use ($form) {
                    $resource = $form->getResource(0);
                    $data = [
                        'title'       => '操作成功',
                        'description' => '表单：' . $form->name . '，创建成功',
                        'createUrl'   => action([FormBuilderController::class, 'create']),
                        'backUrl'     => $resource,
                    ];
                    $msg = $this->executeIndexAndUnique($form->alias, $done->getNewId(), $form);
                    if ($msg) {
                        return $form->error($msg);
                    }
                    return view('admin::form.done-step', $data);
                });
        });
    }

    protected function deleteIndexAndUnique($alias, $id)
    {
        foreach ($this->needDelIndex as $key => $value) {
            DB::select('DROP INDEX CONCURRENTLY IF EXISTS ' . 'index_' . $alias . '_' . $value);
        }
        foreach ($this->needDelUnique as $key => $value) {
            DB::select('DROP INDEX CONCURRENTLY IF EXISTS ' . 'unique_' . $alias . '_' . $value);
        }
    }

    protected function executeIndexAndUnique($alias, $id, $form)
    {
        try {
            foreach ($this->indexSql as $key => $value) {
                $value = str_replace('{index_name}', $alias . '_' . $key, $value);
                $value = str_replace('{form_id}', $id, $value);
                DB::select($value);
            }
            foreach ($this->uniqueSql as $key => $value) {
                $value = str_replace('{index_name}', $alias . '_' . $key, $value);
                $value = str_replace('{form_id}', $id, $value);
                DB::select($value);
            }
        } catch (\Throwable $th) {
            if ($th->errorInfo[0] == '23505') {
                return "索引创建失败，存在重复值。\n" . $th->errorInfo[count($th->errorInfo) - 1];
            }
            return "索引创建失败\n" . $th->errorInfo[count($th->errorInfo) - 1];
        }
        return false;
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
        $form->text('store_tips', '保存提示信息');
        $form->text('update_tips', '更新提示信息');
        $form->text('delete_tips', '删除提示信息');
        $form->select('update_user_auth', '所属账号权限')->options([
            1 => '可编辑',
            0 => '不可编辑',
        ])->required(true);
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
        // TODO 禁止key第一位使用_，下划线系统使用
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
        $fields = [];
        $values = [];
        $components = [];
        if ($isEditing) {
            $components = $form->model()->components['values'];
        } else {
            $session = session('step-form-input:' . admin_controller_slug());
            if (isset($session['components'])) {
                $components = json_decode($session['components']['values'], true);
                if (isset($components['components'])) {
                    $components = $components['components'];
                }
            }
        }
        if ($components) {
            foreach (array_merge(HelperComponent::eachComponents($components), $this->formSubmissionFields) as $key => $value) {
                $fields[] = [
                    'label' => $value['label'],
                    'name' => $key
                ];
                $values[$key] = $value['label'];
            }
        }
        $fields = json_encode($fields);
        $values = json_encode($values);
        $components = Bpm::getGridsForm();
        $components['components'][0]['data']['custom'] = 'values = ' . $fields . ';';
        $components['components'][1]['components'][0]['components'][0]['components'][0]['data']['custom'] = 'values = ' . $fields . ';';
        $components['components'][1]['components'][1]['components'][0]['components'][0]['data']['custom'] = 'values = ' . $fields . ';';
        $formio = $form->bpmFormRender('form')->components($components)->customFormat(function ($value) {
            if ($value && isset($value['fields']) && is_array($value['fields'])) {
                $value['fields'] = array_values($value['fields']);
            }
            if ($value && isset($value['filters']) && is_array($value['filters'])) {
                $value['filters'] = array_values($value['filters']);
            }
            return $value;
        });
        if ($isEditing) {
            $formio->value(['fields' => $form->model()->tables['fields'], 'filters' => $form->model()->tables['filters'], 'title' => $form->model()->tables['title']]);
        }
        Admin::script(<<<JS
        var values = {$values};
        $(document).on('change','.gridField',function(e){
            var myform = Object.values(window.Formio.forms)[0];
            var label = $(this).find('select[ref="selectContainer"]').children('option').html().replace(/&lt;span&gt;/g,'').replace(/&lt;\/span&gt;/g,'');
            myform.getComponent($(this).parent().next().children('.gridLabel').find('input').attr('name').replace('data','')).setValue(label);
        });
JS);
        Admin::style(<<<CSS
        .card.dcat-box .card-header{
            padding:0;
        }
        .open-modal-button{
            margin-top:0;
            padding:0;
            position:absolute;
            top:0;
        }
        .formio-component-modal-wrapper{
            margin:0;
            position:relative;
        }
        [ref=datagrid-fields],[ref=datagrid-filters]{
            width:30%;
        }
        .formio-form{
            margin:0 !important;
        }
        #{$formio->getElementId()} .formio-component-form .formio-form{
            margin:0 10% !important;
        }
CSS);
        $form->hidden('title');
        // $form->textarea('code', '数据处理');
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
