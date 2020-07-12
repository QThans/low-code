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
use Thans\Bpm\Traits\ComponentsEvents;
use Thans\Bpm\Traits\EventsHandle;
use Thans\Bpm\Traits\ShowHandle;
use Thans\Bpm\Traits\UrlBuilderHandle;

use function PHPSTORM_META\map;

class BpmController extends Controller
{
    use EventsHandle, ShowHandle, UrlBuilderHandle, ComponentsEvents;

    /**
     * @var string
     */
    protected $title;
    protected $formId;
    protected $content;
    protected $formData;
    protected $model;
    protected $formComponents;
    protected $showMode = false;
    protected $submission = [];
    /**
     * 组件字段读取
     * @var array
     */
    protected $fields = [];

    protected $actionTitle = [
        'form.index'   => '列表',
        'form.show'   => '查看',
        'form.edit'   => '编辑',
        'form.create' => '创建',
        'form.store' => '',
        'form.update' => '',
        'form.destroy' => '',
    ];

    public function __construct(Content $content)
    {
        $this->formAlias = Request::route()->parameters['alias'];
        $this->formData = ModelForm::with(['apps', 'events', 'components'])->where('alias', $this->formAlias)->first();
        $this->formComponents = $this->formData['components']->values;
        $this->formId = $this->formData['id'];
        $this->title = $this->formData->name;
        $this->formatNav($content);
        $this->content = $content->title($this->title);
        $this->model = FormSubmission::with(['user', 'updatedUser']);
        $this->eventsInit();
        $this->getAllFields($this->formComponents['components']);
    }


    protected $breadcrumb = [];

    public function selfModel()
    {
        return $this->model->where('form_id', $this->formId);
    }

    public function index($alias)
    {
        return $this->content
            ->body($this->grid());
    }

    //表单构建
    public function grid()
    {
        if (request(IFrameGrid::QUERY_NAME)) {
            $grid = new IFrameGrid($this->selfModel());
        } else {
            $grid = new Grid($this->selfModel());
        }
        $grid->showQuickEditButton();
        if (is_array($this->formData->tables->fields)) {
            collect($this->formData->tables->fields)->sortBy('order')->map(function ($value) use ($grid) {
                $grid->column('submission.' . $value['name'], $value['label']);
            })->toArray();
        }
        return $grid;
    }

    public function edit($alias, $id)
    {
        return $this->content
            ->body($this->form()->edit($id));
    }

    public function update($alias, $id)
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
        $data = $this->beforeDataStroe(Request::all());
        return $this->form()->store($data);
    }
    public function form()
    {
        return Form::make($this->model, function ($form) {
            //             Admin::js('@resource-selector');
            //             Admin::script(<<<JS
            //             Dcat.ResourceSelector({
            //                 title: '选择 Select Resource(Multiple)',
            //                 column: "form_alias",
            //                 source: 'http://bpm.bt/admin/auth/users',
            //                 selector: $(replaceNestedFormIndex('.box-header')),
            //                 maxItem: 0, 
            //                 area: ["51%","65%"],
            //                 queryName: '_resource_',
            //                 items: {},
            //                 placeholder: '选择 Select Resource(Multiple)',
            //                 showCloseButton: false,
            //                 disabled: '',
            //                 displayer: 'default',
            //                 displayerContainer: $(replaceNestedFormIndex('.box-header')),
            //             });;
            // JS);
            $form->saving(function (Form $form) {
                $this->submission = (array) $form->data;
                if ($form->isCreating()) {
                    $form->form_alias = $this->formData['alias'];
                    $form->user_id = Admin::user()->id;
                    $form->header = json_encode(Request::header());
                    $form->form_id = $this->formId;
                }
                if ($form->isEditing()) {
                    $form->deleteInput('header');
                }
                $form->updated_user_id = Admin::user()->id;
            });
            $this->eventsHandle($form);
            $formio = $form->bpmFormRender('form')->components($this->formComponents);
            if ($form->isEditing()) {
                $formio->value($form->model()->toArray()['submission']);
            }
            $formio->showMode($this->showMode);
            $form->hidden('form_alias');
            $form->hidden('form_id');
            $form->hidden('user_id');
            $form->hidden('header');
            $form->hidden('updated_user_id');
            $form->hidden('submission')->customFormat(function ($value) {
                return '';
            });
            $form->saving(function (Form $form) {
                $form->submission = $this->submission; //最后处理
            });
        });
    }

    public function formatNav($content)
    {
        return $content->breadcrumb(
            ['text' => $this->title, 'url' => $this->indexUrl()],
            ['text' => $this->actionTitle[Request::route()->action['as']]]
        );
    }

    public function destroy($alias, $id)
    {
        return $this->form()->destroy($id);
    }

    public function getAllFields()
    {
    }
}
