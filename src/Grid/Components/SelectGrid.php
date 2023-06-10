<?php

namespace Thans\Bpm\Grid\Components;

use Thans\Bpm\Http\Controllers\FormController;
use Thans\Bpm\Models\Form;

class SelectGrid extends FieldGrid
{
    public function render()
    {
        return $this->execute();
    }
    public function export()
    {
        $this->object = $this->column;
        $this->column = isset($this->column['submission'][$this->field['name']]) ? $this->column['submission'][$this->field['name']] : null;
        return $this->execute();
    }
    public function execute()
    {
        $alias = str_replace('.', '_', $this->field['name']);
        if (isset($this->options['data']['resource']) && isset((new FormController)->getModels()[$this->options['data']['resource']])) {
            //系统表
            return $this->object[$alias . str_replace('submission.', '', $this->options['labelProperty'])];
        }
        if (isset($this->object[$alias . 'submission'])) {
            //加载对应列的对象
            // dump($this->object);
            $data = json_decode($this->object[$alias . 'submission'], true);
            $mainData = isset($this->object[$alias . 'main']) ? $this->object[$alias . 'main'] : [];
            //获取配置项template并解析
            $isMatched = preg_match_all('/{{(.*?)}}/ims', $this->options['template'], $matches, PREG_SET_ORDER);
            if (!$isMatched) {
                return 'Error';
            }
            $value = '';
            foreach ($matches as $mv) {
                if (count($mv) >= 2) {
                    $key = $mv[count($mv) - 1];
                    $key = trim(str_replace('item.', $alias, $key));
                    $key2Value = '';
                    $key = explode('.', $key);
                    if (count($key) >= 2) {
                        foreach ($key as $mk) {
                            //从data中获取内容
                            if ($key2Value == '') {
                                $key2Value = isset($this->object[$mk]) ? json_decode($this->object[$mk], true) : '';
                            } else {
                                $key2Value = $key2Value[$mk];
                            }
                        }
                    }
                    $value .= ' ' . $key2Value;
                }
            }
            if ($value) {
                return '<span data-title="' . $value . '" data-url="/admin/bpm/' . $this->object[$alias . 'form_alias'] . '/form/' . $this->object[$alias . 'id'] . '?_dialog_=1" class="grid-new-layer-row label" style="background:#5c6bc6">' . $value . '</span>';
            }
            // dump($this->options['labelProperty']);
            //得到Key
            // $key = str_replace('main.', '', str_replace('submission.', '', $this->options['labelProperty']));
            // //判断存在对应value，加载
            // if ($data  && $value = $data[str_replace('submission.', '', $key)]) {
            //     if (request('_export_')) {
            //         return $value;
            //     }
            //     return '<span data-title="' . $value . '" data-url="/admin/bpm/' . $this->object[$alias . 'form_alias'] . '/form/' . $this->object[$alias . 'id'] . '?_dialog_=1" class="grid-new-layer-row label" style="background:#5c6bc6">' . $value . '</span>';
            // }
        }
        //values select
        $values = isset($this->options['data']['values']) ? $this->options['data']['values'] : [];
        foreach ($values as $key => $value) {
            if ($value['value'] == $this->column) {
                return $value['label'];
            }
        }
        return $this->column;
    }
}
