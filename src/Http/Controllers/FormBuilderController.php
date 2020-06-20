<?php

namespace Thans\Bpm\Http\Controllers;

use App\User;
use Dcat\Admin\Admin;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Form\NestedForm;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Widgets\Alert;
use Illuminate\Http\Request;
use Thans\Bpm\Models\Apps;
use Thans\Bpm\Models\Department;
use Thans\Bpm\Models\Form as ModelsForm;
use Thans\Bpm\Models\FormAuthDepartment;
use Thans\Bpm\Models\FormAuthUser;
use Thans\Bpm\Models\FormEvent;


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
        $grid = new Grid(new ModelsForm());
        $grid->id('ID')->bold()->sortable();
        $grid->apps('所属应用');
        $grid->name('表单名称');
        $grid->alias('标识');
        $grid->description('描述');
        $grid->order->orderable();
        $grid->disableBatchDelete();
        $grid->disableEditButton();
        $grid->showQuickEditButton();
        $grid->disableFilterButton();
        $grid->quickSearch(['id', 'name']);
        return $grid;
    }
    public function store()
    {
        return $this->form()->saving(function (Form $form) {
            // 清空缓存
            $form->multipleSteps()->flushStash();
            // 拦截保存操作
            dump($form);
        })->store();
    }
    public function form()
    {
        return Form::make(new ModelsForm(), function ($form) {
            $form->isEditing() ? $form->action(action([FormBuilderController::class, 'update'])) : $form->action(action([FormBuilderController::class, 'store']));;
            $form->disableHeader();
            Admin::style(<<<CSS
            .order{
                width:70px !important;
            }
CSS
);
            $form->multipleSteps()
                ->width('100%')
                ->remember(true)
                ->add('基础信息', function (Form\StepForm $step) {
                    $step->text('name', '表单名称')->required(true);
                    $step->select('apps', '所属应用')->options(Apps::all()->pluck('name', 'id'))->required(true);
                    $step->textarea('description', '描述');
                })
                ->add('表单设计', function (Form\StepForm $step) {
                    $step->bpmFormBuilder('components');
                })
                ->add('事件配置', function (Form\StepForm $step) {
                    $step->table('events', '事件列表', function (NestedForm $table) {
                        $table->select('type', '事件类型')->options(FormEvent::EVENT_TYPE)->load('name', route('bpm.formEvents'));
                        $table->select('name', '事件名称')->addElementClass('field_name');
                        $table->textarea('event', '事件代码');
                    });
                })
                ->add('表单权限', function (Form\StepForm $step) use ($form) {
                    $step->table('auth_department', '部门授权', function (NestedForm $table) {
                        $table->multipleSelect('department', '部门选择')->options(Department::selectOptions());
                        $table->multipleSelect('action', '事件名称')->options(FormAuthDepartment::AUTH_ACTIONS)->help('为空默认为所有');
                    });
                    $step->table('auth_user', '用户授权', function (NestedForm $table) {
                        $table->multipleSelect('user', '用户选择')->options(Administrator::all()->pluck('id', 'name')->toArray());
                        $table->multipleSelect('action', '事件名称')->options(FormAuthUser::AUTH_ACTIONS)->help('为空默认为所有');
                    });
                })
                ->add('数据表格', function (Form\StepForm $step) {
                    $step->table('tables', '数据表格', function (NestedForm $table) {
                        $table->text('label', '字段名称');
                        $table->text('name', '字段标识');
                        $table->number('order', '排序');
                    });
                    $step->table('filters', '筛选字段', function (NestedForm $table) {
                        $table->text('label', '字段名称');
                        $table->text('name', '字段标识');
                        $table->textarea('name', '配置');
                        $table->number('order', '排序');
                    });
                })
                ->done(function () use ($form) {

                    $data = [
                        'title'       => '操作成功',
                        'description' => '恭喜您成为第10086位用户',
                        'createUrl'   => $resource,
                        'backUrl'     => $resource,
                    ];
                    return view('admin::form.done-step', $data);
                });
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
        return constant("Thans\Bpm\Models\FormEvent::$key");
    }
}
