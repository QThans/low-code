<?php

namespace Thans\Bpm\Http\Controllers;

use App\Http\Controllers\Controller;
use DataRole;
use Dcat\Admin\Admin;
use Dcat\Admin\Layout\Content;
use Illuminate\Support\Facades\Request;
use Thans\Bpm\Models\Form as ModelForm;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Exception;
use Thans\Bpm\Compatibility\Grid\IFrameGrid;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Thans\Bpm\Bpm;
use Thans\Bpm\Facades\Grid as FacadesGrid;
use Thans\Bpm\Grid\Actions\Row\DeleteRow;
use Thans\Bpm\Grid\Tools\ExcelExporter;
use Thans\Bpm\Grid\Tools\Filter;
use Thans\Bpm\Helpers\Component as HelperComponent;
use Thans\Bpm\Models\DataRole as ModelsDataRole;
use Thans\Bpm\Models\FormSubmission;
use Thans\Bpm\Models\Role;
use Thans\Bpm\Models\User;
use Thans\Bpm\Syntax\Query;
use Thans\Bpm\Traits\ComponentsEvents;
use Thans\Bpm\Traits\EventsHandle;
use Thans\Bpm\Traits\GridConfig;
use Thans\Bpm\Traits\GridMethods;
use Thans\Bpm\Traits\Resource;
use Thans\Bpm\Traits\ShowHandle;
use Thans\Bpm\Traits\UrlBuilderHandle;

class BpmController extends Controller
{
    use EventsHandle, ShowHandle, UrlBuilderHandle, ComponentsEvents, GridConfig, GridMethods, Resource;

    const RESOURCE_QUERY_NAME = '_grid_resource_';

    protected $submissionKey = 'submission';
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
     * 组件字段KEY
     * @var array
     */
    protected $fields = [];
    /**
     * 组件字段KEY和配置映射
     * @var array
     */
    protected $fieldsOptions = [];

    protected $filters = [];

    // protected $filtersOriginal = [];

    protected $orders = [
        'column' => 'updated_at',
        'type' => 'desc'
    ];


    protected $countFooter = [];

    /**
     * 表单错误信息
     * @var array
     */
    protected $errorMsg = [];

    protected $actionTitle = [
        'form.index' => '列表',
        'form.show' => '查看',
        'form.edit' => '编辑',
        'form.create' => '创建',
        'form.store' => '',
        'form.update' => '',
        'form.destroy' => '',
    ];

    protected $withTable = ['user', 'updateduser', 'createdUser'];

    public function __construct(Content $content)
    {
        $this->formAlias = Request::route()->parameters['alias'];
        $this->formData = ModelForm::with(['apps', 'events', 'components'])->where('alias', $this->formAlias)->first();
        if (!$this->formData) {
            throw new Exception('表单不存在');
        }
        $this->formComponents = $this->formData['components']->values;
        $this->fieldsOptions = $this->formComponents['components'] ? HelperComponent::eachComponents($this->formComponents['components']) : [];
        $this->fields = array_keys($this->fieldsOptions);
        $this->formId = $this->formData['id'];
        $this->title = $this->formData->name;
        $this->formatNav($content);
        $this->content = $content->title($this->title);
        $this->model = FormSubmission::with($this->withTable);
        $this->eventsInit();
        Admin::script(<<<JS
window.formId = {$this->formId};
window.appsId = {$this->formData->apps->id};
JS);
    }

    protected $breadcrumb = [];

    public function selfModel()
    {
        $model = $this->model->where('form_id', $this->formId)->orderBy('form_submissions.status', 'desc');
        //数据域权限判断
        $sql = Bpm::dataAuth($this->formId, $this->fieldsOptions);
        if ($sql) {
            $sql = implode(' or ', $sql);
            $model->whereRaw('( ' . $sql . ' )');
        }
        return $model;
    }

    public function index($alias)
    {
        return $this->content
            ->body($this->grid());
    }

    //列表构建
    public function grid()
    {
        //资源加载
        if (request(self::RESOURCE_QUERY_NAME)) {
            return $this->resourceGrid();
        }
        if (request(IFrameGrid::QUERY_NAME)) {
            $grid = new IFrameGrid($this->selfModel());
        } else {
            $grid = new Grid($this->selfModel());
        }
        if (Request::input('_sort')) {
            $this->orders = Request::input('_sort');
        }
        foreach (Request::all() as $key => $value) {
            if (strpos($key, '_filter_') !== false) {
                $name = str_replace('_', '->', str_replace('_filter_', '', $key));
                $this->filters[] = ['column' => $name, 'value' => $value];
            }
        }
        FacadesGrid::setGrid($grid);
        //筛选 Filter
        $grid->filter(function ($filter) {
            $filter = new Filter($filter, $this->formData->tables->filters, $this->fieldsOptions, $this->formData);
            $filter->render();
        });
        $grid->perPages([10, 20, 30, 50, 100]);
        $grid->withBorder();
        $grid->fixColumns(0, -1);
        $this->quickSearch();
        //暂存状态显示
        $grid->column('status', ' ')->display(function ($column) {
            $icon = '';
            $color = '';
            $title = '';
            switch ($column) {
                case FormSubmission::DEFAULT_STATUS:
                    $icon = FormSubmission::DEFAULT_STATUS_ICON;
                    $color = 'green';
                    $title = FormSubmission::DEFAULT_STATUS_TITLE;
                    break;
                case FormSubmission::SAVE_STATUS:
                    $icon = FormSubmission::SAVE_STATUS_ICON;
                    $color = 'orange';
                    $title = FormSubmission::SAVE_STATUS_TITLE;
                    break;
                case FormSubmission::INVALID_STATUS:
                    $icon = FormSubmission::INVALID_STATUS_ICON;
                    $color = 'red';
                    $title = FormSubmission::INVALID_STATUS_TITLE;
                    break;
                default:
                    break;
            }
            return '<span data-title="' . $title . '" class="row-help fa ' . $icon . '" style="color:' . $color . ';"></span>';
        });
        //数据标题
        $this->rowTitle();
        //字段加载
        if (is_array($this->formData->tables->fields)) {
            FacadesGrid::setFieldsOptions($this->fieldsOptions);
            collect($this->formData->tables->fields)->map(function ($value) {
                if (!isset($value['name'])) {
                    return false;
                }
                $method = $value['name'];
                $column = FacadesGrid::$method($value, $this->formId);
                // to execute the config functions, like: total width and others
                $this->fieldsConfig($column, $value);
                $column->responsive();
            })->toArray();
            // $grid = FacadesGrid::getGrid();
        }
        //导出
        if (Request::get(IFrameGrid::QUERY_NAME) != 1) {
            $grid->export(new ExcelExporter($this->title, $this->fieldsOptions, $this->formData->tables->fields, FacadesGrid::getRows()));
        }
        //权限验证
        $grid->disableCreateButton(!Admin::user()->can('form_create_' . $this->formId));
        $grid->disableViewButton(!Admin::user()->can('form_view_' . $this->formId));
        $grid->disableEditButton(!Admin::user()->can('form_edit_' . $this->formId));
        // $grid->disableDeleteButton(!Admin::user()->can('form_delete_' . $this->formId));
        $grid->disableDeleteButton(true);
        if (Admin::user()->can('form_delete_' . $this->formId)) {
            //重写删除按钮
            $deleteTips = $this->formData['delete_tips'];
            $grid->actions(function (Grid\Displayers\Actions $actions) use ($deleteTips) {
                $actions->append(new DeleteRow($deleteTips));
            });
        }
        $grid->disableQuickEditButton(!Admin::user()->can('form_edit_' . $this->formId));
        $grid->disableQuickEditButton(true);
        //开启或关闭快捷编辑
        //增加通用页面弹出 修改表格样式 修改每页数量选择
        $page = request('page');
        Admin::script(
            <<<JS
$('.grid-new-layer-row').off('click');
$('.grid-new-layer-row').on('click', function () {
    layer.open({
        type: 2,
        title: $(this).attr('data-title'),
        area: ['60%', '80%'],
        content: $(this).attr('data-url'),
    });
    return false;
});
$('.row-help').mouseover(function (){
    if(!$(this).data('title')){
        return false;
    }
    layer.tips($(this).data('title'), $(this).parent(), {
        tips: [1, '#3595CC'],
        time: 1000
    });
});

$('.pagination').next(1).prepend('<input type="number"  class="form-control form-control-sm jump-page" placeholder="跳转" value="" auto="1">');
$('.jump-page').keyup(function(event){
    if(event.keyCode ==13 && $(this).val()){
        var needStr = location.href.indexOf("?") != -1?'':'?';
        Dcat.reload(location.href + needStr + "&_pjax=%23pjax-container&page=" + $(this).val());
    }
    jumpPageDebounce();
});
$('.jump-page').val({$page});
// table-main
var height = 0;
$.each($('.box-footer'),function(){
    height += $(this)[0].offsetHeight;
});
$('.table-main').css('max-height',$(window).height() - 192 - height);
$('.table-main').css('min-height',260);
$('.table-fixed-left,.table-fixed-right').css('max-height',$(window).height() - 204 - height);
$('.table-main').scroll(function(){
    if($(this).scrollTop()!=0){
        $('th').css('border-top','1px solid #e4e4e4');
        $('.sticky-table-header').hide();
    }else{
        $('th').css('border-top','');
        $('.sticky-table-header').show();
    }
    $('.sticky-table-header').remove();
});
$('.sticky-table-header').remove();
$('.box-footer .dropdown-menu').css('position','absolute');
$('.box-footer .dropdown-menu').css('transform','translate3d(0px, -260px, 0px)');
$('.box-footer .dropdown-menu').css('top','0px');
$('.box-footer .dropdown-menu').css('left','0px');
$('.box-footer .dropdown-menu').css('will-change','transform');
$(document).on('click',"button:contains('显示全部')",function() {
    $.each($('input[name^="toggle-grid-table-col-"]'),function(){
        if(!$(this).is(':checked')) {
            $(this).click();
        }
    });
})
var jumpPageDebounce = Dcat.helpers.debounce(function (input) {
    var needStr = location.href.indexOf("?") != -1?'':'?';
    Dcat.reload(location.href + needStr + "&_pjax=%23pjax-container&page=" + $('.jump-page').val());
}, 1200);
JS,
            true
        );
        Admin::style(<<<EOD
        thead > tr > th {
            position: sticky;
            top: 0;
            background: #EFF3F8;
        }
        table {width: 100%;}
        .dt-checkboxes-select thead > tr > th {
            z-index:9;
        }
        .box-footer .dropdown .dropdown-menu:before{
            margin-top:245px;
            border:none;
        }
        .dropdown-menu{
            z-index:99;
        }
        .sticky-table-header{
            display:none;
        }
        .jump-page{
            padding: .54rem .9rem !important;
            box-shadow: 0 3px 1px -2px rgba(0,0,0,.065), 0 2px 2px 0 rgba(0,0,0,.065), 0 1px 5px 1px rgba(0,0,0,.065);
            text-align: center;
            border: 0;
            display: inline-block;
            width: 60px;
            border-radius: .2rem;
            font-size: 12px !important;
            margin-top:2px;
        }
        .box-footer .dropdown-toggle{
            margin-top:-2px;
        }
        .flatpickr-calendar{
            display:none;
        }
EOD);
        //进行排序操作；
        $orderSql = $this->formatOrdersParams();
        if ($orderSql) {
            $grid->model()->orderByRaw($orderSql);
        }
        // if (Request::get(IFrameGrid::QUERY_NAME) == 1) {
        //     $this->resource($grid);
        // }
        //统计
        $this->total($grid);
        return $grid;
    }

    public function total($grid)
    {
        $grid->footer(function ($collection) {
            $footer = '';
            foreach ($this->countFooter as $key => $value) {
                $footer .= '<span style="margin-right:10px;">' . $value['label'] . ' . 总计：' . $value['total'] . '</span>';
            }
            return $footer;
        });
    }

    public function formatOrdersParams()
    {
        $column = explode('.', $this->orders['column']);
        if (count($column) > 1 && !in_array($column[0], $this->withTable)) {
            $column[count($column) - 1] = '\'' . $column[count($column) - 1] . '\'';
            $column = implode('->>', $column);
            return <<<EOD
            "form_submissions".{$column} {$this->orders['type']}
EOD;
        }
        if (count($column) == 1) {
            $column = $column[0];
            return <<<EOD
            {$column} {$this->orders['type']}
EOD;
        }
        return '';
    }

    public function edit($alias, $id)
    {
        return $this->content
            ->body($this->form()->edit($id));
    }

    public function update($alias, $id)
    {
        try {
            DB::beginTransaction();
            FormSubmission::where('id', $id)->lock('for update nowait')->first();
            $ret = $this->form()->update($id);
            DB::commit();
            return $ret;
        } catch (QueryException $th) {
            switch ($th->getCode()) {
                case '23505':
                    return $this->form()->error("新增失败，存在重复值。\n" . $th->errorInfo[count($th->errorInfo) - 1]);
                    break;
                default:
                    return $this->form()->error("数据库异常\n" . $th->errorInfo[count($th->errorInfo) - 1]);
                    break;
            }
        }
    }

    public function create()
    {
        return $this->content
            ->body($this->form());
    }

    public function store()
    {
        try {
            return $this->form()->store();
        } catch (QueryException $th) {
            switch ($th->getCode()) {
                case '23505':
                    return $this->form()->error("新增失败，存在重复值。\n" . $th->errorInfo[count($th->errorInfo) - 1]);
                    break;
                default:
                    return $this->form()->error("数据库异常\n" . $th->getMessage());
                    break;
            }
        }
    }

    public function validation($data, $id = 0)
    {
        $this->errorMsg = [];
        HelperComponent::eachComponents($this->formComponents['components'], '', function ($key, $value) use ($data, $id) {
            if (isset($value['validate']['unique']) && $value['validate']['unique'] && isset($data[$key])) {
                if ($data[$key] != '') {
                    $result = FormSubmission::where('form_id', $this->formId)->where('id', '!=', $id)
                        ->whereRaw('submission->>\'' . $key . '\'::text = ' . '\'' . $data[$key] . '\'')->first();
                    if ($result) {
                        $this->errorMsg[] = $value['label'] . '存在重复值';
                    }
                    return;
                }
                //子表表内判断
                $keys = explode('.', $key);
                if (isset($data[$keys[0]]) && count($keys) == 2) {
                    $has = [];
                    foreach ($data[$keys[0]] as $k => $v) {
                        if (in_array($v[$keys[1]], $has)) {
                            $this->errorMsg[] = $value['label'] . '存在重复值';
                        } else {
                            $has[] = $v[$keys[1]];
                        }
                    }
                }
                // if($value[''])
                // select *,dycs from "form_submissions"
                // left join LATERAL jsonb_path_query(submission,'$.dycs[*]') as dycs on true
                // where "form_id" = 20 and   dycs->>'cs' = '开票资料开票资料要求选择' and "form_submissions"."deleted_at" is null limit 1
                //本子表内容判定
            }
        });
    }

    public function form()
    {
        return Form::make($this->model, function ($form) {
            //权限验证Start
            if ($form->isEditing() && !$this->showMode) {
                $form->disableFooter(!Admin::user()->can('form_put_' . $this->formId));
                $form->disableDeleteButton(!Admin::user()->can('form_delete_' . $this->formId));
                $form->disableViewButton(!Admin::user()->can('form_view_' . $this->formId));
            }
            if ($form->isCreating()) {
                $form->disableFooter(!Admin::user()->can('form_save_' . $this->formId));
            }
            if (!$this->showMode) {
                $saveTips = $form->isEditing() ? $this->formData['update_tips'] : $this->formData['store_tips'];
                $form->disableListButton(!Admin::user()->can('form_index_' . $this->formId));
                $form->disableDeleteButton();
            } else {
                $saveTips = $this->formData['delete_tips'];
            }
            //权限验证End
            $form->saving(function (Form $form) {
                $this->submission = json_decode($form->data, true);
                if (!$this->submission || $this->submission == null || !is_array($this->submission)) {
                    return $form->error('请勿空提交');
                }
                $form->deleteInput('data');
                if ($form->isCreating()) {
                    $form->form_alias = $this->formData['alias'];
                    $form->user_id = Admin::user()->id;
                    $form->header = json_encode(Request::header());
                    $form->form_id = $this->formId;
                    $form->created_user_id = Admin::user()->id;
                }
                if ($form->isEditing()) {
                    $form->deleteInput('header');
                }
                $form->updated_user_id = Admin::user()->id;
                $form->updated_at = date('Y-m-d H:i:s');
            });
            $this->eventsHandle($form, $this->formComponents['components']); //组件处理
            //data field for SQL selection.
            $this->formComponents['components'][] = [
                "type" => "textfield",
                "input" => true,
                "key" => 'creating_user_id',
                "label" => "拥有者",
                "hideLabel" => true,
                "tableView" => true,
                "defaultValue" => isset($form->model()->created_user['id']) && $form->model()->created_user['id'] ? $form->model()->created_user['id'] : Admin::user()->id,
            ];
            Admin::style(<<<EOD
            .formio-component-creating_user_id{
                display:none;
            }
EOD);
            //add the component of user id
            if ($form->isEditing() && !$this->showMode && $this->formData['update_user_auth'] == 1) {
                $userId =  Bpm::getUserIdField();
                $userId['defaultValue'] = $form->model()->user_id;
                $userId['key'] = FormSubmission::USER_ID_FIELD_KEY;
                $this->formComponents['components'][] = $userId;
            }
            $formio = $form->bpmFormRender('form')->components($this->formComponents)->saveTips($saveTips)->formData($form->model()->toArray());
            if ($form->isEditing()) {
                $formio->value($form->model()->toArray()[$this->submissionKey]);
            }
            $formio->showMode($this->showMode);
            $form->hidden('form_alias');
            $form->hidden('form_id');
            $form->hidden('user_id');
            $form->hidden('header');
            $form->hidden('updated_user_id');
            $form->hidden('created_user_id');
            $form->hidden('data');
            $form->hidden('status');
            $form->hidden('submission')->customFormat(function ($value) {
                return '';
            });
            $form->disableEditingCheck();
            $form->saving(function (Form $form) {
                $id = 0;
                //暂存状态处理
                if ($form->isEditing()) {
                    //无效状态单据，拒绝编辑
                    if ($form->model()->status == FormSubmission::INVALID_STATUS) {
                        return $form->error('无效单据，无法编辑');
                    }
                    //暂存状态允许编辑
                    $isSaveStatus = ($form->model()->status == FormSubmission::SAVE_STATUS && $form->input('save-status') == 4);
                    if (!$isSaveStatus) {
                        //禁止编辑字段置空或设为原值。
                        foreach ($this->noEditing as $key => $value) {
                            $this->submission[$value] = isset($form->model()->submission[$value]) ? $form->model()->submission[$value] : '';
                        }
                    }
                    $id = $form->model()->id;
                    //暂存状态，原是暂存状态，并且继续提交暂存状态，保持暂存。这之外不允许暂存
                    $form->status =  $isSaveStatus ? FormSubmission::SAVE_STATUS : FormSubmission::DEFAULT_STATUS;
                    //changing the user id
                    if ($form->isEditing() && $this->formData['update_user_auth'] == 1) {
                        $form->user_id = $this->submission[FormSubmission::USER_ID_FIELD_KEY];
                        unset($this->submission[FormSubmission::USER_ID_FIELD_KEY]);
                    }
                }
                if ($form->isCreating()) {
                    //暂存状态处理
                    $form->status = ($form->input('save-status') == 4) ? FormSubmission::SAVE_STATUS : FormSubmission::DEFAULT_STATUS;
                }
                $this->validation($this->submission, $id);
                if (count($this->errorMsg) >= 1) {
                    return $form->error($this->errorMsg[0]);
                }
            });
            if (request('_dialog_')) {
                $form->disableHeader();
                Admin::style(<<<CSS
                .main-menu,.header-navbar,.breadcrumb{
                    display:none;
                }
                .content-wrapper{
                    margin-left: 0 !important;
                    padding-top: 2.5rem !important;
                }
CSS);
            }
            Admin::style(<<<CSS
                .formio-component-datagrid .filed-content{
                    width: 100%;
                    overflow-y: hidden;
                    overflow-x: auto;
                }
                .formio-component-datagrid .filed-content .is-active{
                    position: inherit;
                }
                .table td{
                    min-width:150px;
                }
CSS);
            $form->saving(function (Form $form) {
                $form->submission = $this->submission; //最后处理
            });
            //增加暂存选项
            if ($form->isCreating() || $form->model()->status == FormSubmission::SAVE_STATUS) {
                $this->addSaveBtn($form->isEditing());
            }
            //删除前事件
            $this->deleting($form);
            //删除后事件
            $this->deleted($form);
            $this->submitted($form);
            $this->saving($form);
            $this->saved($form);
            if ($form->isEditing() && !$this->showMode) {
                $this->editing($form);
            }
            if ($form->isCreating()) {
                $this->creating($form);
            }
            if ($this->showMode) {
                $this->viewing($form);
            }
        });
    }

    //暂存按钮生成
    public function addSaveBtn($isCheck = false)
    {
        $checked = $isCheck ? 'checked="checked"' : '';
        $html = <<<HTML
        <div class="vs-checkbox-con vs-checkbox-primary" style="margin-right: 16px">
            <input value="4" {$checked} name="save-status" circle="1" type="checkbox">
            <span class="vs-checkbox vs-checkbox-">
            <span class="vs-checkbox--check">
                <i class="vs-icon feather icon-check"></i>
            </span>
            </span>
            <span><span class="text-80 text-bold">暂存</span></span>
        </div>
HTML;
        $html = str_replace(array("\r\n", "\r", "\n"), "", $html);
        Admin::script(<<<JS
        $('.box-footer .flex-wrap').append('{$html}');
JS);
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
        try {
            DB::beginTransaction();
            FormSubmission::whereIn('id', explode(',', $id))->lock('for update nowait')->first();
            $ret = $this->form()->destroy($id);
            DB::commit();
            return $ret;
        } catch (QueryException $th) {
            switch ($th->getCode()) {
                case '55P03':
                    return $this->form()->error("暂时无法删除，被使用中");
                    break;
                default:
                    return $this->form()->error("删除失败\n" . $th->getMessage());
                    break;
            }
        }
    }
}
