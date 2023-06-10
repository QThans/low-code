<?php

namespace Thans\Bpm\Traits;

use Thans\Bpm\Facades\Grid as FacadesGrid;

trait GridMethods
{
    public function quickSearch()
    {
        $grid = FacadesGrid::getGrid();
        //快捷搜索
        $grid->quickSearch(function ($model, $query) {
            $quickSearchField = FacadesGrid::getQuickSearch();
            $quickSql = '';
            foreach ($quickSearchField as $key => $value) {
                if ($quickSql == '') {
                    $quickSql = " {$value}::varchar like '%{$query}%' ";
                } else {
                    $quickSql .= " or {$value}::varchar like '%{$query}%' ";
                }
            }
            $model->whereRaw(<<<EOD
    ({$quickSql})
EOD);
        })->auto(false);
    }
    public function rowTitle()
    {
        $grid = FacadesGrid::getGrid();
        if ($this->formData->tables->title) {
            $fieldsOptions = $this->fieldsOptions;
            $titleOption = $this->formData->tables->title;
            $formAlias = $this->formAlias;
            $grid->column('title', '数据标题')->display(function ($column) use ($titleOption, $fieldsOptions, $formAlias) {
                $title = [];
                foreach ($titleOption as $key => $value) {
                    //如果在组件中不存在对应值，则无
                    if (!isset($fieldsOptions[$value])) {
                        $title[] = $this->$value;
                    } else {
                        $fieldValue = $this->submission;
                        foreach (explode(',', $value) as $k => $v) {
                            $fieldValue = isset($fieldValue[$v]) ? $fieldValue[$v] : '';
                        }
                        $title[] =  $fieldValue;
                    }
                }
                return '<a href="/admin/bpm/' . $formAlias . '/form/' . $this->id . '"  title="' . implode(' ', $title) . '">' . implode(' ', $title) . '</a>';
                // return '<span data-title="' . implode(' ', $title) . '" data-url="/admin/bpm/' . $formAlias . '/form/' . $this->id . '?_dialog_=1" class="grid-new-layer-row " style="color:#495abf;">' . implode(' ', $title) . '</span>';
            })->responsive();
        }
    }
}
