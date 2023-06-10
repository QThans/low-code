<?php

namespace Thans\Bpm\Traits;

use Thans\Bpm\Helpers\DocumentNoGenerator;
use Dcat\Admin\Form;
use Ramsey\Uuid\Uuid;
use Thans\Bpm\Helpers\Component as HelperComponent;
use Thans\Bpm\Models\FormSubmission;

trait ComponentsEvents
{
    /**
     * 不可编辑的字段
     * @var array
     */
    protected $noEditing = [];

    protected $eventComponents = [];

    public function eventsHandle($form, $components, $key = '', $index = '')
    {
        foreach ($components as $k => $component) {
            if (isset($component['key'])) {
                $componentKey = $key != '' ? $key . '.' . $component['key'] : $component['key'];
            } else {
                $componentKey = $key != '' ? $key  : '';
            }
            $componentIndex = $index != '' ? $index . '-' . $k : $k;
            if (isset($component['type']) && method_exists($this, $component['type'])) {
                $method = $component['type'];
                $this->$method($form, $component, $componentKey, $componentIndex);
            }
            if ($form->isEditing()) {
                // TODO 适应columns显示
                // Disabled 在更新时，不保存新数据，直接使用旧数据。
                if (isset($component['noEditing']) && $component['noEditing'] && $form->model()->status !== FormSubmission::SAVE_STATUS) {
                    //防止前端数据伪造
                    $this->noEditing[] = $componentKey;
                    $componentIndexs = explode('-', $componentIndex);
                    $componentsPlace = &$this->formComponents;
                    //子组件
                    for ($i = 0; $i < count($componentIndexs); $i++) {
                        if (isset($componentsPlace['components']) && isset($componentsPlace['components'][$componentIndexs[$i]])) {
                            $temp = &$componentsPlace['components'][$componentIndexs[$i]];
                        }
                        if (isset($componentsPlace['columns']) && isset($componentsPlace['columns'][$componentIndexs[$i]])) {
                            $temp = &$componentsPlace['columns'][$componentIndexs[$i]];
                        }
                        unset($componentsPlace);
                        $componentsPlace = &$temp;
                    }
                    //用于组件中判断组件是否也同时是禁止状态。
                    $componentsPlace['originaleDisabled'] = isset($componentsPlace['disabled']) ? $componentsPlace['disabled'] : false;
                    $componentsPlace['disabled'] = isset($componentsPlace['noEditing']) ? $componentsPlace['noEditing'] : false;
                }
            }
            if (isset($component['components'])) {
                $this->eventsHandle(
                    $form,
                    $component['components'],
                    $key ? $key . '.' . $component['key'] : $component['key'],
                    $index != '' ? $index . '-' . $k : $k
                );
            }
            if (isset($component['columns'])) {
                foreach ($component['columns'] as $ck => $cv) {
                    $this->eventsHandle(
                        $form,
                        $cv['components'],
                        $key,
                        $index != '' ? $index . '-' . $ck . '-' . $k : $ck . '-' . $k
                    );
                }
            }
        }
    }

    public function textfield($form, $component, $key, $index)
    {
        if (isset($component['documentNo']) && $component['documentNo']) {
            $this->documentId($form, $component, $key, $index);
        }
    }

    public function checkbox($form, $component, $key, $index)
    {
        $form->saving(function (Form $form) use ($component, $key, $index) {
            $keys = explode('.', $key);
            $submission = &$this->submission;
            for ($i = 0; $i < count($keys); $i++) {
                if (isset($submission[$keys[$i]]) && $i != count($keys) - 1) {
                    $temp = &$submission[$keys[$i]];
                    unset($submission);
                    $submission = &$temp;
                }
            }
            $name = $keys[count($keys) - 1];
            if (isset($submission[$name]) || !isset($submission[0])) {
                //非子级
                $submission[$name] = isset($submission[$name]) && $submission[$name] !== false && $submission[$name] !== 0 ? 1 : 0;
            } elseif (isset($submission[0])) {
                //子级循环
                foreach ($submission as $key => $value) {
                    $submission[$key][$name] = isset($value[$name]) && $value[$name] !== false && $value[$name] !== 0 ? 1 : 0;
                }
            }
        });
    }

    public function radio($form, $component, $key, $index)
    {
        $form->saving(function (Form $form) use ($component) {
            // if (isset($this->submission[$component['key']])) {
            //     $this->submission[$component['key']] = array_values($this->submission[$component['key']])[0];
            // }
        });
    }

    public function datagrid($form, $component, $componentKey)
    {
        $form->saving(function (Form $form) use ($component, $componentKey) {
            if (isset($this->submission[$component['key']]) && $this->submission[$component['key']]) {
                $this->submission[$component['key']] = $this->dataGridUuid($this->submission[$component['key']], isset($form->model()->submission[$component['key']]) ? $form->model()->submission[$component['key']] : [], $form, $componentKey);
                //重置数组数字下标
                $this->submission[$component['key']] = array_merge($this->submission[$component['key']]);
            }
        });
    }

    protected function dataGridUuid($data, $originalData, $form, $componentKey)
    {
        if (!$data) {
            return false;
        }
        foreach ($data as $key => &$value) {
            $value['_uuid'] = isset($value['_uuid']) ? $value['_uuid'] : Uuid::uuid4()->toString();
            //noEditing处理
            if ($form->isEditing()) {
                //禁止编辑字段置空或设为原值。
                foreach ($value as $k => $val) {
                    if (in_array($componentKey . '.' . $k, $this->noEditing)) {
                        //TODO datagrid后端禁止编辑实现
                        // if (isset($originalData[$key]['_uuid']) && ) {
                        //     $value[$k] = isset($originalData[$key][$k]) ? $originalData[$key][$k] : '';
                        // }
                    }
                }
            }
        }
        return $data;
    }

    protected function documentId($form, $component, $key, $index)
    {
        $index = explode('-', $index);
        if ($this->showMode & $form->isEditing()) {
            $components = &$this->formComponents;
            for ($i = 0; $i < count($index); $i++) {
                $temp = &$components['components'][$index[$i]];
                unset($components);
                $components = &$temp;
            }
            $components['prefix'] = '';
            $components['suffix'] = '';
        }
        $form->saving(function (Form $form) use ($component, $key, $index) {
            if ($form->isCreating()) {
                $prefix = isset($component['prefix']) ? $component['prefix'] : '';
                $suffix = isset($component['suffix']) ? $component['suffix'] : '';
                $keys = explode('.', $key);
                $submissions = &$this->submission;
                for ($i = 0; $i < count($keys); $i++) {
                    $temp = &$submissions[$keys[$i]];
                    unset($submissions);
                    $submissions = &$temp;
                }
                $this->submission[$component['key']] = DocumentNoGenerator::generate($this->formData['alias'], $component['documentNo'], $prefix, $suffix);
            }
            if ($form->isEditing()) {
                $this->noEditing[] = $key;
                // remain unchange with the document ID
                $this->submission[$component['key']] = $form->model()->submission[$component['key']];
            }
        });
    }
}
