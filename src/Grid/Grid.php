<?php

namespace Thans\Bpm\Grid;

use Dcat\Admin\Grid as AdminGrid;
use Illuminate\Support\Facades\DB;
use Thans\Bpm\Grid\Tools\ChinaCityRenderable;
use Thans\Bpm\Grid\Tools\MultipleRenderable;
use Thans\Bpm\Grid\Tools\DatagridRenderable;
use Thans\Bpm\Http\Controllers\FormController;
use Thans\Bpm\Models\Form;
use Thans\Bpm\Helpers\Component as HelperComponent;
use Thans\Bpm\Syntax\Query;

class Grid
{
    protected $grid;

    protected $fieldsOptions;

    protected $rows;

    //快捷查询涉及字段
    protected $quickSearch = ['submission', 'id'];

    protected $systemSubSql = [
        'users' => [
            'sub' => '(SELECT id::varchar as {$alias}id,name as {$alias}name,username as {$alias}username,avatar as {$alias}avatar,status as {$alias}status FROM "admin_users") AS {$alias}',
            'select' => '{$alias}id,{$alias}name,{$alias}username,{$alias}avatar,{$alias}status',
        ],
        'roles' => [
            'sub' => '(SELECT id::varchar as {$alias}id,name as {$alias}name,slug as {$alias}slug FROM "admin_roles") AS {$alias}',
            'select' => '{$alias}id,{$alias}name,{$alias}slug',
        ],
        'departments' => [
            'sub' => '(SELECT id::varchar as {$alias}id,name as {$alias}name,parent_id as {$alias}parent_id FROM "departments") AS {$alias}',
            'select' => '{$alias}id,{$alias}name,{$alias}parent_id',
        ],
    ];

    protected $components = [
        'textfield' => \Thans\Bpm\Grid\Components\TextGrid::class,
        'select' => \Thans\Bpm\Grid\Components\SelectGrid::class,
        'checkbox' => \Thans\Bpm\Grid\Components\CheckboxGrid::class,
        'datetime' => \Thans\Bpm\Grid\Components\DateTimeGrid::class,
    ];

    public function getSystemSubSql()
    {
        return $this->systemSubSql;
    }

    public function setGrid(AdminGrid $grid)
    {
        $this->grid = $grid;
        return $this;
    }

    public function setFieldsOptions($fields)
    {
        $this->fieldsOptions = $fields;
        return $this;
    }

    //获取涉及快捷搜索字段
    public function getQuickSearch()
    {
        return $this->quickSearch;
    }

    public function quickSearch($field)
    {
        $this->quickSearch = array_merge($this->getQuickSearch(), explode(',', $field));
        return $this;
    }

    public function getGrid()
    {
        return $this->grid;
    }

    public function setRows($rows)
    {
        return $this->rows = $rows;
    }

    public function getRows()
    {
        return $this->rows;
    }

    public function __call($name, $arguments)
    {
        $field = $arguments[0];
        $formId = $arguments[1];
        //如果在组件中不存在对应值，则无
        if (!isset($this->fieldsOptions[$field['name']])) {
            return $this->grid->column($field['name'], $field['label']);
        }
        $options = $this->fieldsOptions[$field['name']];
        $that = $this;
        $type = $options['type'] . 'Component';
        if (method_exists($that, $type)) {
            $that->$type($field, $options);
        }
        $expandKey = HelperComponent::getExpandKey();
        if (in_array($options['type'], $expandKey)) {
            //数据子表
            if (request('_export_')) {
                $this->rows[$field['name']] = function ($column) use ($formId, $field) {
                    return DatagridRenderable::make(['form_id' => $formId, 'name' => $field['name']])->export($column);
                };
            }
            return $this->grid->column(md5('submission.' . $field['name']), $field['label'])->display('展开')->expand(DatagridRenderable::make(['form_id' => $formId, 'name' => $field['name']]));
        }
        //特殊组件
        if (isset($options['specialType']) && $options['specialType'] == 'chinaCity') {
            return $this->grid->column(md5('submission.' . $field['name']), $field['label'])->display('查看')->modal($field['label'], ChinaCityRenderable::make(['form_id' => $formId, 'name' => $field['name']]));
        }
        if (isset($options['multiple']) && $options['multiple']) {
            //下拉框多选模式，
            // TODO: 文本框多值
            return $this->grid->column(md5('submission.' . $field['name']), $field['label'])->display('查看')->modal($field['label'], MultipleRenderable::make(['form_id' => $formId, 'name' => $field['name']]));
        }
        $display = function ($column) use ($field, $options, $that) {
            if (!isset($that->components[$options['type']])) {
                if (request('_export_')) {
                    if (!isset($column['submission'])) {
                        return $column[$field['name']];
                    }
                    return $column['submission'][$field['name']];
                } else {
                    return $column;
                }
            }
            // TODO: 填充实现
            $type = $options['type'];
            $components = (new $that->components[$type]($column, $field, $options, $this));
            if (request('_export_')) {
                return $components->export();
            } else {
                return $components->render();
            }
        };
        if (request('_export_')) {
            $this->rows[$field['name']] = $display;
        }
        return $this->grid->column('submission.' . $field['name'], $field['label'])->display($display);
    }

    public function selectComponent($field, $options)
    {
        if (isset($options['dataSrc']) && $options['dataSrc'] == 'resource') {
            $this->resource($field, $options);
        }
    }

    public function resource($field, $options)
    {
        $alias = str_replace('.', '_', $field['name']);
        $valueProperty = isset($options['valueProperty']) ? $options['valueProperty'] : ($options['data']['datagrid'] == 'main' ? 'id' : '_uuid');
        $tableAlias = 'form_submissions.submission';
        if (isset($field['tableAlias'])) {
            $tableAlias = $field['tableAlias'];
        }
        $name = $tableAlias . '->' . $field['name'];
        if (isset($options['data']['resource']) && isset((new FormController)->getModels()[$options['data']['resource']])) {
            $sub = DB::raw(str_replace('{$alias}', $alias, $this->systemSubSql[$options['data']['resource']]['sub']));
            //系统表
            $this->grid->model()->selectRaw('form_submissions.*')->selectRaw(str_replace('{$alias}', $alias, $this->systemSubSql[$options['data']['resource']]['select']))
                ->leftJoin($sub, "{$alias}{$valueProperty}", '=', $name);
            $this->quickSearch(str_replace('{$alias}', $alias, $this->systemSubSql[$options['data']['resource']]['select']));
            return;
        }
        if (!isset($options['data']['resource'])) {
            return;
        }
        $form = Form::where('id', $options['data']['resource'])->first();
        //表单存在，并且当前是关联子表时才进行子表关联
        if ($form && isset($options['data']['datagrid']) && $options['data']['datagrid'] != 'main') {
            // 关联自身，子表查询关联 用字段名作为别名
            $datagrid = $options['data']['datagrid'];
            $key = str_replace('main.', '', str_replace('submission.', '', $valueProperty));
            $sub = <<<EOD
                (SELECT replace(jsonb_path_query(submission,'$.{$datagrid}[*].{$key}')::varchar,'"','') AS {$key},
                id::varchar AS {$alias}id,
                form_alias AS {$alias}form_alias,
                submission as {$alias}main,
                jsonb_path_query ( submission, '$.{$datagrid}[*]' )::jsonb AS {$alias}submission from form_submissions WHERE form_alias = '{$form->alias}' and deleted_at is null) AS {$alias}
EOD;
            $this->grid->model()->selectRaw("form_submissions.*,{$alias}submission,{$alias}id,{$alias}main,{$alias}form_alias")
                ->leftJoin(DB::raw($sub), "{$alias}.{$key}", '=', $name);
            $this->quickSearch("{$alias}submission,{$alias}id,{$alias}form_alias");
        }
        //表单存在，并且当前是关联主表时才进行主表关联
        if ($form && isset($options['data']['datagrid']) && $options['data']['datagrid'] == 'main') {
            // 关联自身，子表查询关联 用字段名作为别名
            $datagrid = $options['data']['datagrid'];
            $valueSub = "submission->>{$valueProperty}::varchar AS {$valueProperty},";
            if ($valueProperty == 'id') {
                $valueProperty = "{$alias}id";
                $valueSub = '';
            }
            $sub = <<<EOD
                (SELECT {$valueSub}
                id::varchar AS {$alias}id,
                form_alias AS {$alias}form_alias,
                submission AS {$alias}submission from form_submissions WHERE form_alias = '{$form->alias}') AS {$alias}
EOD;
            $this->grid->model()->selectRaw("form_submissions.*,{$alias}submission,{$alias}id,{$alias}form_alias")
                ->leftJoin(DB::raw($sub), "{$alias}.{$valueProperty}", '=', $name);
            $this->quickSearch("{$alias}submission,{$alias}id,{$alias}form_alias");
        }
    }
}
