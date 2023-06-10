<?php

namespace Thans\Bpm\Repositories;

use Dcat\Admin\Grid;
use Dcat\Admin\Repositories\Repository;
use Thans\Bpm\Http\Controllers\FormController;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Thans\Bpm\Grid\Grid as GridGrid;
use Thans\Bpm\Bpm;
use Thans\Bpm\Models\Form;
use Thans\Bpm\Helpers\Component as HelperComponent;
use Thans\Bpm\Models\FormSubmission;

class IFrameGrid extends Repository
{
    protected $content;
    protected $datagrid;
    protected $options;
    protected $path;
    protected $joinSql = [];
    protected $selectSql = [];
    /**
     * 允许解析的SQL函数
     * @var string[]
     */
    protected $allowSqlFunc = ['select', 'selectRaw','whereRaw','join','subTableJoin'];

    public function setDatagrid($datagrid)
    {
        $this->datagrid = $datagrid == 'main' ? '' : $datagrid;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function setOptions($options)
    {
        $this->options = $options;
    }
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * 查询表格数据
     *
     * @param Grid\Model $model
     * @return LengthAwarePaginator
     */
    public function get(Grid\Model $model)
    {
        $currentPage = $model->getCurrentPage();
        $perPage = $model->getPerPage();
        // 获取筛选参数
        $start = ($currentPage - 1) * $perPage;
        $formController = new FormController();
        /**
         * 禁止select一类函数外其他SQL语句解析，此处不验证范围
         */
        [$sql, $singleItemSql] = $formController->syntax($this->datagrid, $this->content, $this->options[$this->path]['data']['resource'], [], [], [
            'allowFunc' => $this->allowSqlFunc
        ]);
        $selectSql = '';
        foreach ($this->selectSql as $key => $value) {
            if ($selectSql == '') {
                $selectSql = $value;
            } else {
                $selectSql .= ',' . $value;
            }
        }
        $selectSql = $selectSql ? $selectSql : '*';
        if ($this->datagrid) {
            $indexId = '_uuid';
        } else {
            $indexId = 'id';
        }
        //读取数据必须存在于当前列表中。
        //仅当列表筛选条件时必须
        // dump($this->options[$this->path]);
        if (request('_grid_iframe_', 0) == 1) {
            //增加权限判断
            $formData = Form::with(['apps', 'events', 'components'])->where('id', request('form'))->first();
            $formComponents = $formData['components']->values;
            $fieldsOptions = $formComponents['components'] ? HelperComponent::eachComponents($formComponents['components']) : [];
            $authSql = Bpm::dataAuth(request('form'), $fieldsOptions);
            $limitModel = FormSubmission::selectRaw("submission->>'" . $this->options[$this->path]['key'] . "' as " . $this->options[$this->path]['key'] . "")
                ->where('form_id', request('form'));
            if ($authSql) {
                $authSql = implode(' or ', $authSql);
                $limitModel->whereRaw('( ' . $authSql . ' )');
            }
            $limits = $limitModel
                ->get()->toArray();
            if ($limits) {
                foreach ($limits as $lk => $lv) {
                    //防止空数据和数组
                    if ($lv[$this->options[$this->path]['key']] != '') {
                        $limits[$lk] = "'" . $lv[$this->options[$this->path]['key']] . "'";
                    } else {
                        unset($limits[$lk]);
                    }
                }
                $sql = "SELECT *  FROM(" . $sql . ") LIM ";
                $limits = array_unique($limits);
                //有内容，只加载有的
                if ($this->options[$this->path]['valueProperty'] != $indexId) {
                    $limitSql = 'WHERE LIM.submission->>\'' . $indexId . "'::text" . ' in (' . implode(',', $limits) . ')';
                } else {
                    $limitSql = ' WHERE LIM.' . $indexId . ' in (' . implode(',', $limits) . ') ';
                }
                $sql .= $limitSql;
            } else {
                //没有内容，不允许加载
                return $model->makePaginator(
                    0,
                    []
                );
            }
        }
        $sql = "SELECT *,{$selectSql},IFRAME.submission::text,IFRAME.{$indexId}  as {$indexId}  FROM(" . $sql . ") IFRAME ";

        foreach ($this->joinSql as $key => $value) {
            $sql .= $value;
        }
        //模糊搜索实现
        $keyword = request('simple_search_', '');
        if ($keyword) {
            $sql = "{$sql} WHERE concat({$selectSql},IFRAME.submission::text) like '%{$keyword}%'";
        }
        $total = DB::select("SELECT count(*) as total FROM(" . $sql . ") TOTAL");
        $list = DB::select($sql . ' LIMIT ' . $perPage . ' OFFSET ' . $start);
        foreach ($list as $key => $value) {
            unset($value->header);
            unset($value->password);
            if (isset($value->submission)) {
                $value->submission = json_decode($value->submission, true);
            } else {
                $value->submission = json_decode(json_encode($value), true);
            }
            if (isset($value->main)) {
                $value->main = json_decode($value->main);
            }
            $list[$key] = $value;
        }
        return $model->makePaginator(
            $total[0]->total ?? 0,
            $list ?? []
        );
    }
    //进行关联查询，Grid对应方法在此不生效。

    public function join($options, $name, $tableAlias = 'IFRAME.submission')
    {
        $systemSubSql = (new GridGrid())->getSystemSubSql();
        $valueProperty = isset($options['valueProperty']) ? $options['valueProperty'] : ($options['data']['datagrid'] == 'main' ? 'id' : '_uuid');
        $alias = str_replace('.', '_', $name);
        // TODO FILL思路，通过ON关联到fllValue，这样就可以直接读取
        $name = $tableAlias . '->>\'' . $name . "'";

        if (isset($options['data']['resource']) && isset((new FormController)->getModels()[$options['data']['resource']])) {
            $sub = DB::raw(str_replace('{$alias}', $alias, $systemSubSql[$options['data']['resource']]['sub']));
            $this->joinSql[] = 'LEFT JOIN ' . $sub . ' ON ' . "{$alias}{$valueProperty}" . ' = ' . $name;
            //系统表
            $this->selectSql[] = str_replace('{$alias}', $alias, $systemSubSql[$options['data']['resource']]['select']);
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
            $sub = <<<EOD
            LEFT JOIN (SELECT replace(jsonb_path_query(submission,'$.{$datagrid}[*].{$valueProperty}')::varchar,'"','') AS {$valueProperty},
                id::varchar AS {$alias}id,
                form_alias AS {$alias}form_alias,
                submission AS {$alias}main,
                jsonb_path_query ( submission, '$.{$datagrid}[*]' )::jsonb AS {$alias}submission from form_submissions WHERE form_alias = '{$form->alias}') AS {$alias}
                ON {$alias}.{$valueProperty} = {$name}
EOD;
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
            LEFT JOIN (SELECT {$valueSub}
                id::varchar AS {$alias}id,
                form_alias AS {$alias}form_alias,
                submission AS {$alias}submission from form_submissions WHERE form_alias = '{$form->alias}') AS {$alias}
                ON {$alias}.{$valueProperty} = {$name}
EOD;
        }
        $this->selectSql[] = "{$alias}submission,{$alias}id,{$alias}form_alias";
        $this->joinSql[] = $sub;
    }
}
