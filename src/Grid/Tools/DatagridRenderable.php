<?php

namespace Thans\Bpm\Grid\Tools;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid;
use Dcat\Admin\Support\LazyRenderable;
use Illuminate\Support\Facades\DB;
use Thans\Bpm\Models\Form;
use Thans\Bpm\Helpers\Component as HelperComponent;
use Thans\Bpm\Models\FormSubmission;
use Thans\Bpm\Facades\Grid as FacadesGrid;

class DatagridRenderable extends LazyRenderable
{
    public function render()
    {
        // 获取ID
        $id = $this->key;
        $formId = $this->form_id;
        $formData = Form::with(['apps', 'events', 'components'])->where('id', $formId)->first();
        $names = explode('.', $this->name);
        $formComponents = [];
        foreach ($formData['components']->values['components'] as $key => $value) {
            if ($value['key'] == $names[0]) {
                if (count($names) == 2) {
                    foreach ($value['components'] as $k => $v) {
                        if ($v['key'] != $names[1]) {
                            unset($value['components'][$k]);
                        }
                    }
                }
                $formComponents = $value;
            }
        }
        if (!$formComponents) {
            return $this->noData();
        }
        $fieldsOptions = HelperComponent::eachComponents($formComponents['components']);
        $grid = new Grid(new FormSubmission());
        FacadesGrid::setGrid($grid);
        FacadesGrid::setFieldsOptions($fieldsOptions);
        $grid->model()->leftJoin(DB::raw("jsonb_path_query(submission, '$.{$names[0]}[*] ') as {$names[0]}"), 'id', '=', 'id');
        $grid->disableActions();
        $grid->disablePagination();
        $grid->disableToolbar();
        $grid->disablePerPages();
        $grid->disableRowSelector();
        foreach ($fieldsOptions as $key => $value) {
            $value['name'] = $value['key'];
            $value['tableAlias'] = $names[0];
            $method = $value['name'];
            FacadesGrid::$method($value, $formId);
        }
        $grid = FacadesGrid::getGrid();
        $grid->withBorder();
        $grid->model()->selectRaw("
        *,{$names[0]} AS submission
    ")->where('form_id', $formId)->where('id', $id);
        $grid->render();
        // $grid->model()->select('id');
        // $array = $grid->processFilter(true);
        // dump($array);
        // die();
        return $grid;
        // return Table::make($title, $data);
    }
    public function noData()
    {
        return '<p style="text-align:center;">无数据</p>';
    }
    public function export($column)
    {
        $id = $column['id'];
        $formId = $this->form_id;
        $formData = Form::with(['apps', 'events', 'components'])->where('id', $formId)->first();
        $names = explode('.', $this->name);
        $formComponents = [];
        foreach ($formData['components']->values['components'] as $key => $value) {
            if ($value['key'] == $names[0]) {
                if (count($names) == 2) {
                    foreach ($value['components'] as $k => $v) {
                        if ($v['key'] != $names[1]) {
                            unset($value['components'][$k]);
                        }
                    }
                }
                $formComponents = $value;
            }
        }
        if (!$formComponents) {
            return $this->noData();
        }
        HelperComponent::setFields([]);
        $fieldsOptions = HelperComponent::eachComponents($formComponents['components']);
        $grid = new Grid(new FormSubmission());
        FacadesGrid::setGrid($grid);
        FacadesGrid::setRows([]);
        FacadesGrid::setFieldsOptions($fieldsOptions);
        $grid->model()->leftJoin(DB::raw("jsonb_path_query(submission, '$.{$names[0]}[*] ') as {$names[0]}"), 'id', '=', 'id');
        $grid->disableActions();
        $grid->disablePagination();
        $grid->disableToolbar();
        $grid->disablePerPages();
        $grid->disableRowSelector();
        $fields = [];
        $titles = [];
        foreach ($fieldsOptions as $key => $value) {
            $value['name'] = $value['key'];
            $value['tableAlias'] = $names[0];
            $method = $value['name'];
            $fields[] = $value;
            $titles[] = $value['label'];
            FacadesGrid::$method($value, $formId);
        }
        $rows = FacadesGrid::getRows();
        $grid = $grid->model()->selectRaw("
        *,{$names[0]} AS submission
    ")->where('form_id', $formId)->where('id', $id);
        $items = [];
        foreach ($grid->buildData() as $item) {
            $i = '';
            foreach ($fields as $key => $value) {
                if (isset($value['name']) && isset($rows[$value['name']])) {
                    $label = $value['label'];
                    $val = $rows[$value['name']]($item);
                    $i .= $label . "：" . $val . PHP_EOL;
                }
                // $i[$key] = $value;
            }
            $items[] = $i;
        }
        return $items;
    }
}
