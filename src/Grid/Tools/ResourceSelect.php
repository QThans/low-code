<?php

namespace Thans\Bpm\Grid\Tools;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Thans\Bpm\Http\Controllers\FormController;
use Thans\Bpm\Models\Form;
use Thans\Bpm\Models\FormSubmission;
use Thans\Bpm\Syntax\Query;

/**
 * 用于select组件，resource模式的数据获取等公共操作
 * Class ResourceSelect
 * @package Thans\Bpm\Grid\Tools
 */
class ResourceSelect
{
    protected $componentOptions;

    public function __construct($componentOptions)
    {
        $this->componentOptions = $componentOptions;
    }

    public function resource()
    {
        return $this->componentOptions['data']['resource'];
    }

    public function datagrid()
    {
        return $this->componentOptions['data']['datagrid'];
    }

    public function valueProperty()
    {
        return $this->componentOptions['valueProperty'];
    }

    /**
     * 获取系统表值，多数组
     * @param $value
     * @return mixed
     */
    public function getResourceValues($value)
    {
        if ($this->isSystem()) {
            $model = (new FormController)->getModels()[$this->componentOptions['data']['resource']];
            $model = new $model;
            $model = $model->whereIn($this->componentOptions['valueProperty'], $value);
        } else {
            //读取表单配置
            $formData = Form::where('id', $this->resource())->first();
            if ($this->isMain()) {
                $valueSub = $this->valueProperty() . ",submission->>'" . str_replace('submission.', '', $this->componentOptions['labelProperty']) . "'::varchar AS " . str_replace('submission.', '', $this->componentOptions['labelProperty']);
                $model = (new FormSubmission())->setAlias($formData->alias)->where('form_id', $this->resource())
                    ->selectRaw($valueSub);
            } else {
                //子表
                $model = (new FormSubmission())->setAlias($formData->alias)->where('form_id', $this->resource())
                    ->selectRaw($this->getSearchSelectRawSql($value));
            }
        }
        if (isset($this->componentOptions) && isset($this->componentOptions['dataFiltering']) && $this->componentOptions['dataFiltering']) {
            $query = new Query();
//            $model = $model->setAlias('MCPZ');
//            $model = (new FormSubmission())->setAlias($this->formData->alias)->where($this->formData->alias . '.form_id', $this->formId)->where($this->formData->alias . '.status', FormSubmission::DEFAULT_STATUS);
            $model = $query->render($this->componentOptions['dataFiltering'], [], $model, true);
            $sql = $model->toSql();
            $sql = str_replace('\?', '********', $sql);
            $bindings = $model->getBindings();
            foreach ($bindings as $binding) {
                if (is_array($binding)) {
                    $binding = implode(',', $binding);
                    $sql = preg_replace('/\?/', $binding, $sql, 1);
                } else {
                    $sql = preg_replace('/\?/', "'" . $binding . "'", $sql, 1);
                }
            }
            $sql = str_replace('********', '?', $sql);
            $list = DB::select($sql);
            $model = collect($list);
        }
        $model = $model->pluck(str_replace('submission.', '', $this->componentOptions['labelProperty']), $this->valueProperty());
        return $model->filter(function ($v) {
            if ($v) {
                return true;
            }
        });
    }

    /**
     * 是否系统表资源
     * @return bool
     */
    public function isSystem()
    {
        if (isset($this->componentOptions['data']['resource']) && isset((new FormController)->getModels()[$this->componentOptions['data']['resource']])) {
            return true;
        }
        return false;
    }

    /**
     * 是否主表资源
     * @return bool
     */
    public function isMain()
    {
        if (isset($this->componentOptions['data']['datagrid']) && $this->componentOptions['data']['datagrid'] == 'main') {
            return true;
        }
        return false;
    }

    public function getSelectRawSql()
    {
        return DB::raw("form_id,
                form_alias,
                user_id,
                updated_user_id,
                header,
                created_at,
                updated_at,
                deleted_at,
                replace(jsonb_path_query ( submission, '$." . $this->datagrid() . "[*]." . $this->valueProperty() . "' )::varchar,'\"','')  AS " . $this->valueProperty() . ",
                jsonb_path_query ( submission, '$." . $this->datagrid() . "[*]' )::jsonb AS submission
            ");
    }

    public function getSearchSelectRawSql($value)
    {
        $sql = '';
        if (is_array($value)) {
            foreach ($value as $val) {
                //组合多种情况
                $sql .= $sql == '' ? "@." . $this->valueProperty() . " == \"" . $val . "\"" : " ||  @." . $this->valueProperty() . " == \"" . $val . "\"";
            }
            $sql = '(' . $sql . ')';
        } else {
            $sql = "(@." . $this->valueProperty() . " == \"" . $value . "\")";
        }
        $label = str_replace('submission.', '', $this->componentOptions['labelProperty']);
        return DB::raw("form_id,
                form_alias,
                user_id,
                updated_user_id,
                header,
                created_at,
                updated_at,
                deleted_at,
                replace(jsonb_path_query ( submission, '$." . $this->datagrid() . "[*]." . $this->valueProperty() . "' )::varchar,'\"','')  AS " . $this->valueProperty() . ",
                replace(jsonb_path_query ( submission, '$." . $this->datagrid() . "[*] \? " . $sql . "." . $label . "')::varchar,'\"','')  AS " . $label . ",
                jsonb_path_query ( submission, '$." . $this->datagrid() . "[*] \? " . $sql . "' )::jsonb AS submission
            ");
    }
}
