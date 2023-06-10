<?php

namespace Thans\Bpm\Traits;

use Dcat\Admin\Admin;
use Illuminate\Support\Str;
use Dcat\Admin\Grid;
use Dcat\Admin\Grid\Tools\QuickSearch;
use Illuminate\Support\Facades\DB;
use Thans\Bpm\Bpm;
use Thans\Bpm\Models\FormSubmission;
use Thans\Bpm\Facades\Grid as FacadesGrid;
use Thans\Bpm\Grid\Column\Filter\Between;
use Thans\Bpm\Grid\Column\Filter\Equal;
use Thans\Bpm\Grid\Column\Filter\Like;
use Thans\Bpm\Grid\Column\Filter\In;
use Thans\Bpm\Grid\Tools\Filter;
use Thans\Bpm\Grid\Grid as NewGrid;

trait GridConfig
{
    protected $gridConfig = [];

    protected $columnFilters = [];

    // protected $gridConfigGrid;

    public function fieldsConfig($column, $field, $canUseConfig = [])
    {
        if (isset($field['configs']) && is_array($field['configs'])) {
            $this->gridConfig = $field['configs'];
            foreach ($field['configs'] as $key => $config) {
                $method = 'config' . Str::ucfirst($key);
                if (((count($canUseConfig) > 0 && in_array($key, $canUseConfig)) || count($canUseConfig) == 0) && method_exists($this, $method)) {
                    $this->$method($column, $key, $config);
                }
            }
        }
    }

    public function configSortable($column, $key, $config)
    {
        $column->$key();
    }

    public function configWidth($column, $key, $config)
    {
        if ($config) {
            $column->$key($config);
        }
    }

    /**
     * 列筛选 列过滤器
     * @param $column
     * @param $key
     * @param $config
     */
    public function configColumnFilter($column, $key, $config)
    {
        $type = false;
        if (isset($this->gridConfig['timeType'])) {
            $config = $this->gridConfig['timeType']; //对于存在时间类型的,赋值时间类型.
        }
        switch ($config) {
            case 'date':
                $type = isset($this->gridConfig['isRange']) ? Between::make()->date() : Equal::make()->date();
                break;
            case 'time':
                $type = isset($this->gridConfig['isRange']) ? Between::make()->time() : Equal::make()->time();
                break;
            case 'datetime':
                $type = isset($this->gridConfig['isRange']) ? Between::make()->datetime($this->gridConfig['timeFormat']) : Equal::make()->datetime($this->gridConfig['timeFormat']);
                break;
            case 'equal':
                $type = Equal::make();
                break;
            case 'like':
                $type = Like::make();
                break;
            case 'select':
                $type = In::make(collect($this->gridConfig['selectMap'])->pluck('title', 'value')->toArray());
                break;
        }
        if ($type) {
            $name = $this->formatFieldParams($column->getName());
            $this->columnFilters[$name] = ['type' => $config, 'gridConfig' => $this->gridConfig];
            $column->filter($type->valueFilter()->setColumnName($name));
        }
    }
    //the total of the column
    public function configTotal($column, $key, $config)
    {
        if (strpos($column->getName(), 'submission') === false) {
            return;
        }
        $columnKey = str_replace('submission.', '', $column->getName());
        $sub = <<<EOD
            (SELECT
            id as total_id,NULLIF(replace(jsonb_path_query ( submission, '$.{$columnKey}[*]' )::text,'"','')::float,0) AS {$columnKey} from form_submissions WHERE form_alias = '{$this->formAlias}') AS total
EOD;
        $model = FormSubmission::where('form_alias', $this->formAlias)->with($this->withTable);
        $grid = FacadesGrid::getGrid();
        //Re-instant the new Grid to execute the all actions of grid. To get the final model to count.
        $newGrid = new NewGrid();
        $m = $grid->model()->repository();
        $grid = new Grid($m);
        $newGrid->setGrid($grid);
        $grid->filter(function ($filter) {
            $filter = new Filter($filter, $this->formData->tables->filters, $this->fieldsOptions, $this->formData);
            $filter->render();
        });
        //字段加载
        if (is_array($this->formData->tables->fields)) {
            $newGrid->setFieldsOptions($this->fieldsOptions);
            collect($this->formData->tables->fields)->map(function ($value) use ($newGrid) {
                if (!isset($value['name'])) {
                    return false;
                }
                $method = $value['name'];
                $column = $newGrid->$method($value, $this->formId);
                $column->responsive();
            })->toArray();
        }
        $grid->quickSearch(function ($model, $query) use ($newGrid) {
            $quickSearchField = $newGrid->getQuickSearch();
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
        $grid->model()->select('id');
        $grid->paginate($model->count());
        $array = $grid->processFilter(true);
        $model->whereIn('id', array_column($array, 'id'));
        foreach ($this->filters as $key => $value) {
            if ($value['column'] == 'id') {
                continue;
            }
            $filter = isset($this->columnFilters[$value['column']]) ? $this->columnFilters[$value['column']] : '';

            if ($filter) {
                switch ($filter['type']) {
                    case 'date':
                    case 'time':
                    case 'datetime':
                        if (isset($filter['gridConfig']['isRange'])) {
                            if (empty($value['value'])) {
                                break;
                            }
                            if (!isset($value['value']['start']) || $value['value']['start'] == '') {
                                $where = $value['column'] . ' <= \'' . $value['value']['end'] . "'";
                                break;
                            }

                            if (!isset($value['value']['end']) || $value['value']['end'] == '') {
                                $where = $value['column'] . ' >= \'' . $value['value']['start'] . "'";
                                break;
                            }
                            $where = $value['column'] . ' between \'' . $value['value']['start'] . "' and '" . $value['value']['end'] . "'";
                        } else {
                            $where = $value['column'] . ' = \'' . $value['value'] . '\'';
                        }
                        break;
                    case 'equal':
                        $where = $value['column'] . ' = \'' . $value['value'] . '\'';
                        break;
                    case 'like':
                        $where = <<<EOD
                "form_submissions".{$value['column']}::varchar like  '%{$value['value']}%'
EOD;
                        break;
                    case 'select':
                        if (empty($value['value'])) {
                            break;
                        }
                        for ($i = 0; $i <  count($value['value']); $i++) {
                            $value['value'][$i] =  "'" . $value['value'][$i] . "'";
                        }
                        $value['value'] = implode(',', $value['value']);
                        $where = $value['column'] . ' in ' . "({$value['value']})";
                        break;
                }
            } else {
                $where = <<<EOD
                "form_submissions".{$value['column']}::varchar like  '%{$value['value']}%'
EOD;
            }
            if ($where) {
                $model = $model->whereRaw($where);
            }
        }
        //数据域权限判断
        $sql = Bpm::dataAuth($this->formId, $this->fieldsOptions);
        if ($sql) {
            $sql = implode(' or ', $sql);
            $model->whereRaw('( ' . $sql . ' )');
        }

        $columnName = $this->formatFieldParams($column->getName());
        $total = $model->selectRaw('SUM(total.' . $columnKey . ') as total');
        $total = $total->leftJoin(DB::raw($sub), 'total.total_id', '=', 'id')->first()->toArray();
        $prefix = isset($this->gridConfig['total_prefix']) ? $this->gridConfig['total_prefix'] : '';
        $total = isset($this->gridConfig['total_decimal']) ? number_format($total['total'], $this->gridConfig['total_decimal']) : number_format($total['total']);
        $suffix = isset($this->gridConfig['total_suffix']) ? $this->gridConfig['total_suffix'] : '';
        $this->countFooter[] = ['label' => $column->getLabel(), 'total' => $prefix . $total . $suffix];
    }


    //submission筛选字段合成
    public function formatFieldParams($column)
    {
        $column = explode('.', $column);
        if (count($column) > 1 && $column[0] == 'submission') {
            $column[count($column) - 1] = '\'' . $column[count($column) - 1] . '\'';
            $column = implode('->>', $column);
        } else {
            if (count($column) > 1 && $column[0] != 'submission') {
                $column = implode('.', $column);
            } else {
                if (count($column) == 1) {
                    return $column[0];
                }
            }
        }
        return $column;
    }

    public function configLimit($column, $key, $config)
    {
        if ($config) {
            $column->limit($config, '...');
        }
    }
}
