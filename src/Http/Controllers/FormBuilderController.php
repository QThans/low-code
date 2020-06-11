<?php

namespace Thans\Bpm\Http\Controllers;

use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Form\NestedForm;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Widgets\Alert;
use Illuminate\Http\Request;
use Thans\Bpm\Models\Form as ModelsForm;
use Thans\Bpm\Models\FormEvent;

class FormBuilderController extends AdminController
{
    /**
     * @var string
     */
    protected $title = '表单';

    protected $description = [
        'index'  => '',
        'show'   => '',
        'edit'   => '',
        'create' => '创建',
    ];

    //表单构建
    public function grid()
    {
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
            $form->multipleSteps()
                ->width('100%')
                ->remember()
                ->add('基础信息', function (Form\StepForm $step) {
                    $step->text('name', '表单名称')->required(true);
                    $step->textarea('description', '描述');
                })
                ->add('表单设计', function (Form\StepForm $step) {
                    $step->bpmFormBuilder('components');
                })
                ->add('事件配置', function (Form\StepForm $step) {
                    $step->table('events', '事件列表', function (NestedForm $table) {
                        $table->select('type', '事件类型')->options(FormEvent::EVENT_TYPE)->load('name', route('bpm.formEvents'));
                        $table->select('name', '事件名称');
                        $table->textarea('event', '事件代码');
                    });
                })
                ->add('表单权限', function (Form\StepForm $step) {
                    $step->select('address', '权限组');
                })
                ->add('数据列表', function (Form\StepForm $step) {
                    $step->text('address', '街道地址');
                    $step->text('post_code', '邮政编码');
                    $step->tel('tel', ' 联系电话');
                })
                ->done(function () use ($form) {

                    $resource = $form->getResource(0);

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
