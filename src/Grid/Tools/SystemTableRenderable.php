<?php

namespace Thans\Bpm\Grid\Tools;

use Dcat\Admin\Widgets\Table;
use Illuminate\Support\Facades\DB;
use Thans\Bpm\Http\Controllers\FormController;
use Thans\Bpm\Models\Area;
use Thans\Bpm\Models\City;
use Thans\Bpm\Models\Department;
use Thans\Bpm\Models\Form;
use Thans\Bpm\Models\FormSubmission;
use Thans\Bpm\Models\Province;
use Thans\Bpm\Models\Role;
use Thans\Bpm\Models\User;
use Dcat\Admin\Grid\LazyRenderable;
use Dcat\Admin\Grid;
use PhpOffice\PhpSpreadsheet\Worksheet\Dimension;

/**
 * 系统表Renderable
 * Class MultipleRenderable
 * @package Thans\Bpm\Grid\Tools
 */
class SystemTableRenderable extends LazyRenderable
{
    public function grid(): Grid
    {
        // 获取外部传递的参数
        $title = $this->title;
        $valueProperty = $this->valueProperty;
        $key = $this->key;
        $formId = $this->formId;
        $systemTable = $this->systemTable;
        $fieldIsMulti = $this->fieldIsMulti;
        $selectRaw = $this->systemField ? $key : "submission->>'" . $key . "' as " . $key . "";
        $limits = FormSubmission::selectRaw($selectRaw)->where('form_id', $formId)->get()->toArray();
        $data = [];
        if ($limits) {
            foreach ($limits as $lk => $lv) {
                if ($fieldIsMulti) {
                    $lv[$key] = json_decode($lv[$key]);
                    foreach ($lv[$key] as $v) {
                        $data[] = $v;
                    }
                } else {
                    $data[] = $lv[$key];
                }
            }
        }
        $data = array_unique($data);
        $model = new $systemTable;
        if ($limits) {
            $model = $model->whereIn($valueProperty, $data);
        }
        return Grid::make($model, function (Grid $grid) use ($title) {
            $grid->column('id');
            $grid->column('username');
            $grid->column('name');
            $grid->column('created_at');
            $grid->column('updated_at');

            $grid->rowSelector()->titleColumn($title);

            $grid->quickSearch(['id', 'username', 'name']);

            $grid->paginate(10);

            $grid->disableActions();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('username')->width(4);
                $filter->like('name')->width(4);
            });
        });
    }
}
