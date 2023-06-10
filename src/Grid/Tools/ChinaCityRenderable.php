<?php

namespace Thans\Bpm\Grid\Tools;

use Dcat\Admin\Support\LazyRenderable;
use Dcat\Admin\Widgets\Table;
use Thans\Bpm\Models\Area;
use Thans\Bpm\Models\City;
use Thans\Bpm\Models\Form;
use Thans\Bpm\Models\FormSubmission;
use Thans\Bpm\Models\Province;

class ChinaCityRenderable extends LazyRenderable
{
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
        $submission = FormSubmission::where('id', $id)->first();
        // dump($submission[$formComponents['key']]);
        if (!$submission || !isset($submission['submission'][$formComponents['key']])) {
            return false;
        }
        $submission = $submission['submission'][$formComponents['key']];
        $translation = ['Province' => '省份', 'City' => '城市', 'Area' => '区县', 'Address' => '地址'];
        $data = [];
        foreach ($formComponents['components'] as $val) {
            $title[] = isset($translation[$val['label']]) ? $translation[$val['label']] : $val['label'];
            if (!isset($submission[$val['key']])) {
                $data[] = '';
                continue;
            }
            $value = $submission[$val['key']];
            switch ($val['specialType']) {
                case 'province':
                    $province = Province::where('code', $value)->first();
                    $data[] = $province ? $province['name'] : '';
                    break;
                case 'city':
                    $city = City::where('code', $value)->first();
                    $data[] = $city ? $city['name'] : '';
                    break;
                case 'area':
                    $area = Area::where('code', $value)->first();
                    $data[] = $area ? $area['name'] : '';
                    break;
                default:
                    $data[] = $value;
                    break;
            }
        }
        return Table::make($title, [$data]);
    }
}
