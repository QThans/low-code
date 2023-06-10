<?php

namespace Thans\Bpm\Traits;

use Dcat\Admin\Admin;
use Illuminate\Support\Facades\Cache;
use Thans\Bpm\Compatibility\Grid\IFrameGrid;
use Thans\Bpm\Facades\Grid as FacadesGrid;
use Illuminate\Support\Facades\Request;
use Thans\Bpm\Helpers\Component as HelperComponent;
use Thans\Bpm\Syntax\Query;
use Thans\Bpm\Models\Form as ModelForm;
use Thans\Bpm\Models\FormSubmission;
use Illuminate\Support\Facades\DB;
use Thans\Bpm\Repositories\IFrameGrid as RepositoriesIFrameGrid;

trait Resource
{
    //弹窗资源加载
    public function resourceGrid()
    {
        $select = "";
        //是否子表
        $datagrid = request('datagrid', '');
        $grid = FacadesGrid::getGrid();
        $form = Request::get('form');
        $path = Request::get('path');
        $form = ModelForm::with(['apps', 'events', 'components'])->where('id', $form)->first();
        $options = HelperComponent::eachComponents($form['components']->values['components']);
        $list = [];
        // $formData = Request::session()->get('temp_' . Request::get('form') . Request::get('token'));
        $formData = Cache::get('temp_' . Request::get('form') . Request::get('token'));
        $formData = json_decode(json_encode($formData), false);
        $content = json_decode(json_encode(['query' => isset($options[$path]['dataFiltering']) ? $options[$path]['dataFiltering'] : [], 'formData' => $formData]));

        $model = new RepositoriesIFrameGrid();
        $model->setContent($content);
        $model->setDatagrid($datagrid);
        $model->setOptions($options);
        $model->setPath($path);
        $datagridFieldsOptions = [];
        $datagridFields = [];

        if (!$datagrid || !isset($this->fieldsOptions[$datagrid])) {
            //主表内容显示
            if (is_array($this->formData->tables->fields)) {
                collect($this->formData->tables->fields)->map(function ($value) use ($model) {
                    if (!isset($value['name'])) {
                        return false;
                    }
                    if (isset($this->fieldsOptions[$value['name']])) {
                        $this->compatibleWithIFrameGrid($this->fieldsOptions[$value['name']], $value['name'], $model);
                    }
                })->toArray();
            }
        } else {
            foreach ($this->fieldsOptions as $key => $value) {
                if (strpos($key, $datagrid . '.') !== false) {
                    $datagridFields[] = [
                        'name' => $value['key'],
                        'label' => $value['label'],
                    ];
                    $datagridFieldsOptions[$value['key']] = $value;
                }
            }
            foreach ($datagridFields as $key => $value) {
                //兼容IFrameGrid
                $this->compatibleWithIFrameGrid($datagridFieldsOptions[$value['name']], $value['name'], $model);
            }
        }
        $grid = new IFrameGrid($model);

        FacadesGrid::setGrid($grid);
        Admin::script(<<<JS
            $('.grid-new-layer-row').off('click');
JS);
        //当前列表对应表单
        $originalFormData = ModelForm::with(['apps', 'events', 'components'])->where('id', \request('form'))->first();
        $originalFormData = $originalFormData['components']->values;
        $originalFormData = $originalFormData['components'] ? HelperComponent::eachComponents($originalFormData['components']) : [];
        //根据字段加载label，确定titleColumn
        $grid->rowSelector()->titleColumn($originalFormData[\request('path')]['labelProperty']);
        $grid->setKeyName($originalFormData[\request('path')]['valueProperty']);
        if (!$datagrid || !isset($this->fieldsOptions[$datagrid])) {
            //主表内容显示
            if (is_array($this->formData->tables->fields)) {
                FacadesGrid::setFieldsOptions($this->fieldsOptions);
                collect($this->formData->tables->fields)->map(function ($value) use ($grid) {
                    if (!isset($value['name'])) {
                        return false;
                    }
                    $method = $value['name'];
                    $column = FacadesGrid::$method($value, $this->formId);
                    $column->responsive();
                })->toArray();
            }
        } else {
            FacadesGrid::setGrid($grid);
            FacadesGrid::setRows([]);
            HelperComponent::setFields([]);
            FacadesGrid::setFieldsOptions($datagridFieldsOptions);
            foreach ($datagridFields as $key => $value) {
                $value['name'] = $value['name'];
                $value['tableAlias'] = $datagrid;
                $method = $value['name'];
                $column = FacadesGrid::$method($value, $this->formId, $datagrid);
                $column->responsive();
            }
        }
        $this->quickSearch();
        return $grid;
    }
    public function compatibleWithIFrameGrid($options, $alias, $model)
    {
        switch ($options['type']) {
            case 'select':
                $model->join($options, $alias);
                break;
        }
    }
}
