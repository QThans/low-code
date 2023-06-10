<?php

namespace Thans\Bpm\Grid\Tools;

use Dcat\Admin\Support\LazyRenderable;
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

/**
 * 多选内容显示
 * Class MultipleRenderable
 * @package Thans\Bpm\Grid\Tools
 */
class MultipleRenderable extends LazyRenderable
{
    protected $component;

    public function render()
    {
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
            return false;
        }
        $this->component = $formComponents;
        $submission = FormSubmission::where('id', $id)->first();
        if (!$submission || !isset($submission['submission'][$formComponents['key']])) {
            return false;
        }
        $submission = $submission['submission'][$formComponents['key']];
        $data = [];
        $title[] = $formComponents['label'];
        $datas = $this->select($submission);
        foreach ($datas as $val) {
            $data[] = [$val];
        }
        return Table::make($title, $data);
    }

    public function select($value)
    {
        $resourceSelect = new ResourceSelect($this->component);
        return $resourceSelect->getResourceValues($value)->map(function ($val) {
            return $val;
        })->toArray();
    }
}
