<?php

namespace Thans\Bpm\Grid\Tools;

use Dcat\Admin\Admin;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Worksheet\Dimension;
use Thans\Bpm\Grid\Filter\Equal;
use Thans\Bpm\Grid\Filter\Presenter\SelectResource;
use Thans\Bpm\Http\Controllers\FormController;
use Thans\Bpm\Models\Form;
use Thans\Bpm\Models\FormSubmission;
use Thans\Bpm\Helpers\Component as HelperComponent;
use Thans\Bpm\Models\User;

class Filter
{
    protected $filter;

    protected $filtersOptions;

    protected $fieldsOptions;

    protected $formData;

    public function __construct($filter, $filtersOptions, $fieldsOptions, $formData)
    {
        $this->filter = $filter;
        $this->filtersOptions = $filtersOptions;
        $this->fieldsOptions = $fieldsOptions;
        $this->formData = $formData;
    }

    public function render()
    {
        //筛选加载
        foreach ($this->filtersOptions as $key => $value) {
            if (isset($value['configs']) && isset($value['configs']['type'])) {
                $name = $value['name'];
                //是否存在对应组件
                if (!isset($this->fieldsOptions[$name])) {
                    //不存在对应组件的室系统字段。固定加载。
                    $field = HelperComponent::isSystemField($value['name']);
                    if ($field) {
                        $method = $value['configs']['type'];
                        $value['originalName'] = $value['name'];
                        $this->$method($value, [
                            'label' => $field['label'],
                            'name' => $name
                        ]);
                    }
                    continue;
                }
                $method = $value['configs']['type'];
                if (isset($this->fieldsOptions[$value['name']])) {
                    $value['originalName'] = $value['name'];
                    $value['name'] = strpos('.', $value['name']) === false ? 'submission->' . $value['name'] : 'submission->' . str_replace('.', '->', $value['name']);
                }
                $this->$method($value, $this->fieldsOptions[$name]);
            }
        }
    }

    public function checkbox($column)
    {
        $this->filter->like($column['name'], $column['label'])->select([1 => '是', 0 => '否']);
        Admin::script(
            <<<EOD
$('select[name="{$column['name']}"]').select2({"allowClear":true,"placeholder":{"id":"","text":"\u9009\u62e9"}});
EOD
        );
    }

    public function time($column)
    {
        $this->filter->between($column['name'], $column['label'])->datetime();
        $name = $column['name'];
        Admin::script(
            <<<EOD
            $('input[name="{$name}[start]"]').datetimepicker({"format":"YYYY-MM-DD HH:mm:ss","locale":"zh-CN"});
            $('input[name="{$name}[end]"]').datetimepicker({"format":"YYYY-MM-DD HH:mm:ss","locale":"zh-CN","useCurrent":false});
            $('input[name="{$name}[start]"]').on("dp.change", function (e) {
                $('input[name="{$name}[end]"]').data("DateTimePicker").minDate(e.date);
            });
            $('input[name="{$name}[end]"]').on("dp.change", function (e) {
                $('input[name="{$name}[start]"]').data("DateTimePicker").maxDate(e.date);
            });;
EOD
        );
    }

    public function like($column)
    {
        $this->filter->like($column['name'], $column['label']);
    }

    public function equal($column)
    {
        $this->filter->equal($column['name'], $column['label']);
    }

    public function select($column, $fieldsOptions)
    {
        try {
            if (!isset($column['configs']['selectType'])) {
                return false;
            }
            switch ($column['configs']['selectType']) {
                case 'resource':
                    //判断是否系统表
                    $formController = new FormController;
                    if (HelperComponent::isSystemField($column['name'])) {
                        $names = explode('.', $column['name']);
                        if (count($names) != 2) {
                            return false;
                        };
                        $systemTable = $formController->getModels()['users'];
                        $key = $names[0] == 'user' ? 'user_id' : 'updated_user_id';
                        $this->filter->equal($key, $column['label'])
                            ->selectTable(SystemTableRenderable::make(['systemTable' => $systemTable, 'title' => $names[1], 'valueProperty' => 'id', 'key' => $key, 'formId' => $this->formData['id'], 'systemField' => true])) // 设置渲染类实例，并传递自定义参数
                            ->title($fieldsOptions['label'])
                            ->model(User::class, 'id', $names[1]); // 设置编辑数据显示
                        return true;
                    }
                    if (!isset($fieldsOptions['data']['resource'])) {
                        return false;
                    }
                    $fieldIsMulti = false;
                    // 多选组件筛选处理
                    if (isset($fieldsOptions['multiple']) && $fieldsOptions['multiple']) {
                        $fieldIsMulti = true;
                        $filter = $this->filter->where($column['label'], function ($query) use ($column) {
                            $name = str_replace('submission->', '"form_submissions"."submission"->>\'', $column['name'] . "'");
                            $query->whereRaw(
                                <<<EOD
                            ($name)::jsonb  @> '[$this->input]'::jsonb
EOD
                            );
                        });
                    } else {
                        $filter = $this->filter->equal($column['name'], $column['label']);
                    }
                    if (isset($formController->getModels()[$fieldsOptions['data']['resource']])) {
                        //系统表
                        $systemTable = $formController->getModels()[$fieldsOptions['data']['resource']];
                        $label = str_replace('submission.', '', $fieldsOptions['labelProperty']);
                        $filter->selectTable(SystemTableRenderable::make(['systemTable' => $systemTable, 'fieldIsMulti' => $fieldIsMulti, 'title' => $label, 'valueProperty' => $fieldsOptions['valueProperty'], 'key' => $fieldsOptions['key'], 'formId' => $this->formData['id']])) // 设置渲染类实例，并传递自定义参数
                            ->title($fieldsOptions['label'])
                            ->model(User::class, $fieldsOptions['valueProperty'], $label); // 设置编辑数据显示
                        return true;
                    }
                    $form = Form::where('id', $fieldsOptions['data']['resource'])->first();
                    if (!$form) {
                        return false;
                    }
                    $path = 'bpm/' . $form['alias'] . '/form?form=' . $this->formData['id'] . '&path=' . $column['originalName'] . '&_grid_resource_=1&_filter_=0&datagrid=' . ($fieldsOptions['data']['datagrid'] != 'main' ? $fieldsOptions['data']['datagrid'] : '');
                    $filter->setPresenter(new SelectResource($column['name']))->path($path)
                        ->options(function ($v) use ($fieldsOptions) { // options方法用于显示已选中的值
                            if (!$v) return $v;
                            $resourceSelect = new ResourceSelect($fieldsOptions);
                            return $resourceSelect->getResourceValues(array_merge($v, $v))->toArray();
                        });
                    Admin::script(
                        <<<EOD
                    $("div[class*='{$column['name']}'] .pull-right").click(function(){
                          $('input[name="{$column['name']}"]').val('');
                          $('div[name="{$column['name']}"]').html('');
                    });
EOD
                    );
                    break;
                case 'values':
                    $data = collect($column['configs']['valuesData'])->pluck('label', 'value');
                    $this->filter->equal($column['name'], $column['label'])->select($data);
                    Admin::script(
                        <<<EOD
        $('select[name="{$column['name']}"]').select2({"allowClear":true,"placeholder":{"id":"","text":"\u9009\u62e9"}});
EOD
                    );
                    break;
                case 'load_values':
                    if (isset($fieldsOptions['data']['values']) && is_array($fieldsOptions['data']['values'])) {
                        $data = collect($fieldsOptions['data']['values'])->pluck('label', 'value');
                        $this->filter->equal($column['name'], $column['label'])->select($data);
                        Admin::script(
                            <<<EOD
            $('select[name="{$column['name']}"]').select2({"allowClear":true,"placeholder":{"id":"","text":"\u9009\u62e9"}});
EOD
                        );
                    }
                    break;
            }
            Admin::script(
                <<<EOD
        $('select[name="{$column['name']}"]').css('width','auto');
EOD
            );
        } catch (\Throwable $th) {
            admin_warning('筛选解析错误', $column['label'] . '：筛选解析错误，请检查配置。错误信息：' . $th->getMessage());
        }
    }
}
