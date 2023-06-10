<?php

namespace Thans\Bpm\Grid\Tools;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid\Exporters\AbstractExporter;
use Illuminate\Support\Facades\DB;
use Thans\Bpm\Grid\Grid;
use Thans\Bpm\Models\FormSubmission;
use Thans\Bpm\Models\User;

class ExcelExporter extends AbstractExporter
{
    protected $titles = [];
    protected $fieldsOptions = [];
    protected $fields = [];
    protected $rows = [];
    protected $filename = 'export.xlsx';

    protected $merge = [];

    public function  __construct($title = '', $fieldsOptions = [], $fields, $rows)
    {
        $this->fieldsOptions = $fieldsOptions;
        $this->rows = $rows;
        $this->fields = $fields;
        $this->filename = $title . '-' . date('YmdHis') . '.xlsx';
        parent::__construct($this->titles);
    }
    public function export()
    {
        ini_set("memory_limit", 0);
        // if (Admin::user()['id'] != 1) {
        //     echo '导出暂停服务';
        //     die();
        // }
        $maxSize = 100000;
        $this->titles['status'] = '状态';
        //记录哪些字段为子表
        $datagrid = [];
        $datagridFields = [];
        $fields = [];
        //保存导出字段配置
        $fieldsOptions = [];
        foreach ($this->fields as $key => $value) {
            if (isset($this->fieldsOptions[$value['name']]['type']) && $this->fieldsOptions[$value['name']]['type'] == 'datagrid') {
                $datagrid[] = 'submission.' . $value['name'];
                foreach ($this->fieldsOptions[$value['name']]['components'] as $k => $v) {
                    $datagridFields['submission.' . $value['name']][] = 'submission.' . $value['name'] . '.' . $v['key'];
                    $this->titles['submission.' . $value['name'] . '.' . $v['key']] = $value['label'] . '.' . $v['label'];
                    $fieldsOptions['submission.' . $value['name'] . '.' . $v['key']] = $v;
                }
                $fieldsOptions['submission.' . $value['name']] = $this->fieldsOptions[$value['name']];
            } else {
                if (isset($this->fieldsOptions[$value['name']])) {
                    $this->titles['submission.' . $value['name']] = $value['label'];
                    $fields[] = 'submission.' . $value['name'];
                    $fieldsOptions['submission.' . $value['name']] = $this->fieldsOptions[$value['name']];
                } else {
                    $this->titles[$value['name']] = $value['label'];
                    $fields[] = $value['name'];
                }
            }
        }
        $y = 0;
        $items = [];
        //select 等预设键值对形式数据保存
        $defaultKeyValue = [];
        //多选资源,需要集中查询字段值存储
        $multipleSelectData = [];
        foreach ($this->buildData(1, $maxSize) as $key => $value) {
            $y++;
            $item = [];
            foreach ($fields as $fk => $fv) {
                $fieldValue = $this->arraySubset($value, $fv);
                if (isset($fieldsOptions[$fv])) {
                    switch ($fieldsOptions[$fv]['type']) {
                        case 'select':
                            //单选，资源
                            //满足条件：单选、资源组件
                            // $canMultiple = !(!isset($fieldsOptions[$fv]['canMultiple']) || $fieldsOptions[$fv]['canMultiple'] == false);
                            $canMultiple = is_array($fieldValue);
                            $isResource = isset($fieldsOptions[$fv]['data']['resource']) && $fieldsOptions[$fv]['data']['resource'];
                            if (!$canMultiple && $isResource) {
                                //资源SQL已载入
                                if (isset($value[$fieldsOptions[$fv]['key'] . 'submission'])) {
                                    //根据label获取值
                                    $label = str_replace('submission.', '', $fieldsOptions[$fv]['labelProperty']);
                                    $submission =  json_decode($value[$fieldsOptions[$fv]['key'] . 'submission'], true);
                                    $fieldValue = $submission[$label];
                                }
                                //系统字段读取
                                if (isset((new Grid())->getSystemSubSql()[$fieldsOptions[$fv]['data']['resource']])) {
                                    //根据label读取值 系统字段没有聚合的submission 都是:yyzusername之类
                                    $label = str_replace('submission.', $fieldsOptions[$fv]['key'], $fieldsOptions[$fv]['labelProperty']);
                                    if (isset($value[$label])) {
                                        $fieldValue = $value[$label];
                                    }
                                }
                            }
                            //预设键值对形式读取
                            if (!isset($fieldsOptions[$fv]['data']['resource'])) {
                                if (!isset($defaultKeyValue[$fieldsOptions[$fv]['key']])) {
                                    $selectValues = [];
                                    foreach ($fieldsOptions[$fv]['data']['values'] as $v) {
                                        $selectValues[$v['value']] = $v['label'];
                                    }
                                    $defaultKeyValue[$fieldsOptions[$fv]['key']] = $selectValues;
                                }
                                //单选,键值对形似
                                if (!$canMultiple && isset($defaultKeyValue[$fieldsOptions[$fv]['key']][$fieldValue])) {
                                    $fieldValue = $defaultKeyValue[$fieldsOptions[$fv]['key']][$fieldValue];
                                }
                                //多选,键值对形式
                            }
                            // 多选,资源
                            if ($canMultiple && $isResource) {
                                if (isset($multipleSelectData[$fv])) {
                                    $multipleSelectData[$fv] = array_unique(array_merge($fieldValue, $multipleSelectData[$fv]));
                                } else {
                                    $multipleSelectData[$fv] = $fieldValue;
                                }
                            }
                            break;
                        case 'checkbox':
                            $fieldValue = $fieldValue == 0 ? '否' : '是';
                            break;
                        default:
                            break;
                    }
                }
                $item[0][$fv] = $fieldValue;
                $item[0]['status'] = (new FormSubmission)->getStatusTitle($value['status']);
            }
            foreach ($datagrid as $dk => $dv) {
                $datagridValues = $this->arraySubset($value, $dv);
                if($datagridValues){
                    foreach ($datagridValues as $dvk => $dvv) {
                        if (!is_array($dvv) || count($dvv) == 0) {
                            continue;
                        }
                        foreach ($dvv as $datagridValuesChildKKey => $datagridValuesChild) {
                            $item[$dvk][$dv . '.' . $datagridValuesChildKKey] = $datagridValuesChild;
                        }
                    }
                }
            }
            $items = array_merge($items, $item);
        }
        //多选资源组件内容查询
        $multipleSelectValue = [];
        foreach ($multipleSelectData as $key => $value) {
            switch ($fieldsOptions[$key]['data']['resource']) {
                case 'users':
                    $rows = User::whereIn('id', $value)->pluck('username', 'id');
                    $multipleSelectValue[$key] = $rows;
                    break;

                default:
                    break;
            }
        }
        foreach ($items as $key => $value) {
            foreach ($multipleSelectValue as $k => $v) {
                if (isset($value[$k])) {
                    foreach ($value[$k] as $ck => $cv) {
                        if(isset($v[$cv])){
                            $value[$k][$ck] = $v[$cv];
                        }
                    }
                    $value[$k] = implode(',', $value[$k]);
                }
            }
            $items[$key] = $value;
        }
        $export = new Export(collect($items));
        $export->setMap($this->titles);
        $excel = ($export)->download($this->filename);
        response()->download($excel->getFile()->getRealPath(), $this->filename)->prepare(request())->send();
        exit;
    }

    public function arraySubset($array, $keys)
    {
        $keys = explode('.', $keys);
        foreach ($keys as $key) {
            if (isset($array[$key])) {
                $array = $array[$key];
            } else {
                return null;
            }
        }
        return $array;
    }

    public function map($item): array
    {
        // This example will return 3 rows.
        // First row will have 2 column, the next 2 will have 1 column
        return [
            [
                $item['id'],
                $item['updated_at']
            ]
        ];
    }
}
