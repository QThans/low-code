<?php

namespace Thans\Bpm\Http\Controllers;

use App\Http\Controllers\Controller;
use Dcat\Admin\Admin;
use Dcat\Admin\Layout\Content;
use Illuminate\Support\Facades\Request;
use Thans\Bpm\Models\Form as ModelForm;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\IFrameGrid;
use Thans\Bpm\Models\FormSubmission;
use Thans\Bpm\Traits\EventsHandle;

class BpmController extends Controller
{
    use EventsHandle;

    /**
     * @var string
     */
    protected $title;
    protected $formId;
    protected $content;
    protected $formData;
    protected $model;

    protected $actionTitle = [
        'form.index'   => '列表',
        'form.show'   => '查看',
        'form.edit'   => '编辑',
        'form.create' => '创建',
        'form.store' => '',
        'form.edit' => '',
        'form.update' => '',
    ];

    public function __construct(Content $content)
    {
        $this->formId = Request::route()->parameters['id'];
        $this->formData = ModelForm::with(['apps', 'departments', 'users', 'events', 'components'])->find($this->formId);
        $this->title = $this->formData->name;
        $this->formatNav($content);
        $this->content = $content->title($this->title);
        $this->model = new FormSubmission();
        $this->eventsInit();
    }


    protected $breadcrumb = [];

    public function selfModel()
    {
        return $this->model->where('form_id', $this->formId);
    }

    public function index($formId)
    {
        if (request(IFrameGrid::QUERY_NAME)) {
            return $this->content->perfectScrollbar()->body($this->grid());
        }
        return $this->content
            ->body($this->grid());
    }

    //表单构建
    public function grid()
    {
        $grid = new Grid($this->selfModel());
        collect($this->formData->tables->fields)->sortBy('order')->map(function ($value) use ($grid) {
            $grid->column('submission.' . $value['name'], $value['label']);
        })->toArray();
        return $grid;
    }

    public function edit($formId, $id, Content $content)
    {
        return $content
            ->body($this->form()->edit($id));
    }

    public function update($formId, $id)
    {
        return $this->form()->update($id);
    }

    public function create()
    {
        return $this->content
            ->body($this->form());
    }
    public function store()
    {
        //数据事件：新增前
        $data = $this->beforeDataStroe($this->request->all());
        return $this->form()->store($data);
    }
    public function form()
    {
        return Form::make($this->model, function ($form) {
            $formio = $form->bpmFormRender('form')->components($this->formData['components']->values);
            if ($form->isEditing()) {
                $formio->value($form->model()->toArray()['submission']);
            }
            $form->hidden('form_alias');
            $form->hidden('user_id');
            $form->hidden('header');
            $form->hidden('updated_user_id');
            $form->hidden('submission');
            $form->saving(function (Form $form) {
                if ($form->isCreating()) {
                    $form->form_alias = $this->formData['alias'];
                    $form->user_id = Admin::user()->id;
                    $form->header = json_encode(Request::header());
                }
                $form->submission = $form->data;
                $form->updated_user_id = Admin::user()->id;
            });
        });
    }

    public function formatNav($content)
    {
        return $content->breadcrumb(
            ['text' => $this->title, 'url' => action([BpmController::class, 'index'], ['id' => $this->formId])],
            ['text' => $this->actionTitle[Request::route()->action['as']]]
        );
    }
}
