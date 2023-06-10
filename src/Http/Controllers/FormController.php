<?php

namespace Thans\Bpm\Http\Controllers;

use App\Http\Controllers\Controller;
use Dcat\Admin\Admin;
use Dcat\Admin\Models\Menu;
use Dcat\Admin\Widgets\Dump;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Thans\Bpm\Bpm;
use Thans\Bpm\Models\Department;
use Thans\Bpm\Models\Form;
use Thans\Bpm\Models\FormSubmission;
use Thans\Bpm\Models\Role;
use Thans\Bpm\Models\User;
use Thans\Bpm\Syntax\Query;
use Thans\Bpm\Helpers\Component as HelperComponent;
use Thans\Bpm\Models\Form as ModelForm;

class FormController extends Controller
{
    protected $canUseType = ['resource'];

    protected $models = [
        'users' => 'Thans\Bpm\Models\User',
        'roles' => 'Thans\Bpm\Models\Role',
        'departments' => 'Thans\Bpm\Models\Department',
    ];

    protected $modelsColumn = [
        'users' => ['username', 'name', 'id'],
        'roles' => ['slug', 'name', 'id'],
        'departments' => ['parent_id', 'name', 'id'],
    ];

    public function index(Request $request)
    {
        // TODO 完成权限，和表单管理权限挂钩
        $type = $request->type;
        return in_array($type, $this->canUseType) ? $this->$type($request) : [];
    }

    /**
     * 资源读取
     * @param Request $request
     * @return array
     * @throws BindingResolutionException
     */
    protected function resource(Request $request)
    {
        if (!$request->select) {
            return [];
        }
        $select = explode(',', $request->select);
        $select = collect($select)->map(function ($value, $key) {
            switch ($value) {
                case '_id':
                    return 'id as _id';
                    break;
                case 'title':
                    return 'name as title';
                    break;
            }
            return $value;
        });
        $forms = Form::select($select->toArray())->with(['components'])
            ->whereHas('components', function ($query) {
                $query->whereNotNull('values');
            })->get()->toArray();

        return array_merge($forms, $this->systemForm());
    }

    protected function systemForm()
    {
        return [
            [
                '_id' => 'users',
                'title' => admin_trans_field(Menu::where('uri', 'auth/users')->first()->title),
                'components' => null
            ],
            [
                '_id' => 'roles',
                'title' => admin_trans_field(Menu::where('uri', 'auth/roles')->first()->title),
                'components' => null
            ],
            [
                '_id' => 'departments',
                'title' => admin_trans_field(Menu::where('uri', 'bpm/department')->first()->title),
                'components' => null
            ]
        ];
    }

    public function users()
    {
        $components = User::components();
        return $components;
    }

    public function roles()
    {
        $components = Role::components();
        return $components;
    }

    public function departments()
    {
        $components = Department::components();
        return $components;
    }

    public function detail($id)
    {
        // TODO 需要具有表单权限
        if (method_exists($this, $id)) {
            return $this->$id();
        }
        $form = Form::where('id', $id)->with(['components'])->with(['components'])
            ->whereHas('components', function ($query) {
                $query->whereNotNull('values');
            })->first();
        return $form ? $form : [];
    }

    public function getModels()
    {
        return $this->models;
    }

    public function submission(Request $request, $id)
    {
        $requests = [];
        $filters = [];
        $alias = '';
        foreach ($request->all() as $key => $value) {
            if (strpos($key, '__regex') !== false) { // Need to focus on restrict
                $key = str_replace('__regex', '', $key);
                $key = str_replace('_', '->', $key);
                $requests[] = [$key, $value];
            }
            if ($key == '__value__') {
                $filters = array_merge($filters, json_decode($value, true));
            }
            if ($key == 'alias') {
                $alias = $value;
            }
        }
        $limit = $request->get('limit', '100');
        $skip = $request->get('skip', '0');

        // TODO 需要对应表单权限，系统表单怎么处理？
        $datagrid = $request->input('datagrid', '');
        $content = json_decode($request->getContent());
        [$sql, $singleItemSql] = $this->syntax($datagrid, $content, $id, $filters, $requests, [], $alias);
        //封装SQL  数据域权限
        if (!isset($this->models[$id])) {
            $components = ModelForm::with(['apps', 'events', 'components'])->where('id', $id)->first();
            $components = $components['components']->values['components'];
            HelperComponent::setFields([]);
            $fieldsComponent = HelperComponent::eachComponents($components);
            $dataRoleSql = Bpm::dataAuth($id, $fieldsComponent, stristr($sql, 'main') === false ? 'submission' : 'main');
            if ($dataRoleSql) {
                $dataRoleSql = implode(' or ', $dataRoleSql);
                $sql = "SELECT * FROM( " . $sql . " ) D  WHERE ($dataRoleSql)";
            }
        }
        // dump($sql);die();
        //分页处理
        $list = DB::select($sql . ' LIMIT ' . $limit . ' OFFSET ' . $skip);
        if ($singleItemSql) {
            $singleList = DB::select($singleItemSql . ' LIMIT ' . $limit . ' OFFSET ' . $skip);
            $list = array_merge($singleList, $list);
        }
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
        return $list;
    }

    public function syntax($datagrid, $content, $id, $filters = [], $requests = [], $queryOptions = [], $alias = '')
    {
        if (isset($this->models[$id])) {
            $formSubmission = (new $this->models[$id]());
        }
        $select = $datagrid ? DB::raw("form_id,
        form_alias,
        user_id,
        updated_user_id,
        header,
        created_at,
        updated_at,
        deleted_at,
        submission as main
        ,jsonb_path_query ( submission, '$." . $datagrid . "[*]' )::jsonb AS submission
    ") : '';
        $query = new Query($queryOptions);
        if ($content && $content->query) {
            if (!isset($this->models[$id])) {
                $form = Form::find($id);
                //仅正式提交状态，可被资源使用。
                $formSubmission = (new FormSubmission())->setAlias($form->alias)->where($form->alias . '.form_id', $id)->where($form->alias . '.status', FormSubmission::DEFAULT_STATUS);
            }

            if (isset($this->models[$id])) {
                $formSubmission = (new $this->models[$id]());
            }
            if ($datagrid && preg_match('/as\ *submission/i', $content->query)) {
                //新增功能会判断某些函数执行，某些不执行。因此需要再加入selectRaw是否执行判断
                //当不执行selectRaw时，存在 as submission 不影响，因此只有执行时才清除。
                if ($query->isExcuMethod('selectRaw')) {
                    //检查query中是否存在submission，存在则剔除当前表中submission，子表查询时
                    $select = str_replace(",jsonb_path_query ( submission, '$." . $datagrid . "[*]' )::jsonb AS submission", '', $select);
                }
            }
            $formSubmission = $select ? $formSubmission
                ->selectRaw($select) : $formSubmission;
            //语法解析，模型查询
            $subSqls = []; //仅针对子表构造完成后查询的特殊语句
            $formSubmission = $query->render($content->query, $content->formData, $formSubmission, false, $subSqls);
        } else {
            //仅正式提交状态，可被资源使用。
            $formSubmission = $formSubmission ?? (new FormSubmission())->where('form_id', $id)->where('status', FormSubmission::DEFAULT_STATUS);
            $formSubmission = $select ? $formSubmission
                ->selectRaw($select) : $formSubmission;
        }
        $bindings = $formSubmission->getBindings();
        //$requests
        foreach ($requests as $value) {
            $key = $value[0];
            $val = $value[1];
            $val = str_replace('\'', '\'\'', $val);
            $whereMethod = 'like';
            if (is_array(explode(',', $val)) && count(explode(',', $val)) >= 2) {
                $vals = explode(',', $val);
                $val = '';
                foreach ($vals as $v) {
                    $val .= $val == '' ? "'" . $v . "'" : ",'" . $v . "'";
                }
                $val = "(" . $val . ")";
                $whereMethod = 'in';
            }
            if ($whereMethod == 'like') {
                $val = "'%" . $val . "%'";
            }
            if (isset($this->models[$id])) {
                $columns = $this->modelsColumn[$id];
                $likeSql = '';
                foreach ($columns as $column) {
                    if ($likeSql != '') {
                        $likeSql .= ' or ';
                    }
                    $likeSql .= $column . '::varchar ' . $whereMethod . '' . $val . '';
                }
                $formSubmission = $formSubmission->whereRaw(<<<EOD
                    ({$likeSql})
EOD);
            } else {
                $a = $alias ? '"' . $alias . '".' : '';
                if (!$select) {
                    $select = ($select ? $select : '*') . ',' . $a . 'submission::jsonb as submission';

                    $formSubmission = $formSubmission->selectRaw($select)->whereRaw(<<<EOD
                        ({$a}"submission"::varchar {$whereMethod} {$val} or {$a}id::varchar {$whereMethod} {$val})
EOD);
                } else {
                    $formSubmission = $formSubmission->whereRaw(<<<EOD
                        ({$a}"submission"::varchar {$whereMethod} {$val} or {$a}id::varchar {$whereMethod} {$val})
EOD);
                }
            }
        }
        if (!$datagrid) {
            $singleSubmission = clone $formSubmission;
            $hasSingle = false;
            foreach ($filters as $key => $value) {
                if ($key != '' && $value != '') {
                    $key = $key == 'id' ? 'id' : 'submission.' . $key;
                    // the default alias of the table
                    if ($alias) {
                        $key = '"' . $alias . '"' . '.' . $key;
                    }
                    $isMulti = explode(',', $value);
                    if (count($isMulti) > 1) {
                        $singleSubmission = $singleSubmission->whereRaw(<<<EOD
                        $key in ($value)
EOD);
                    } else {
                        $singleSubmission = $singleSubmission->whereRaw(<<<EOD
                        $key = $value
EOD);
                    }
                    $hasSingle = true;
                }
            }
            //读取当前选择
            $singleItemSql = '';
            if ($hasSingle) {
                $singleItemSql = $singleSubmission->toSql();
                $singleItemSql = $this->paraphrase($singleItemSql, $bindings);
            }
        }
        $sql = $formSubmission->toSql();

        $sql = $this->paraphrase($sql, $bindings);

        //子表查询情况下，封装为子查询语句，实现uuid切换
        if ($datagrid) {
            //主表内容设置
            $sql = "SELECT *,submission::jsonb->>'_uuid' as _uuid  FROM(" . $sql . ") t ";

            //子表subSqls执行
            if (isset($subSqls) && $subSqls) {
                foreach ($subSqls as $key => $value) {
                    $method = $value['method'];
                    $sql = $query->$method($sql, $value);
                }
            }
            //重新包裹
            $sql = "SELECT * FROM(" . $sql . ") Z ";
            $whereSql = [];
            foreach ($filters as $key => $value) {
                if ($key != '' && $value != '') {
                    $isMulti = explode(',', $value);
                    if (count($isMulti) > 1) {
                        $whereSql[] = " submission->>'" . $key . "' in '(" . $value . ")' ";
                    } else {
                        $whereSql[] = " submission->>'" . $key . "' = '" . $value . "' ";
                    }
                }
            }
            //读取当前选择
            $singleItemSql = '';
            if (count($whereSql) > 0) {
                $singleItemSql = $sql;
                $singleItemSql .= ' where ' . implode(' and ', $whereSql);
            }
        }
        return [$sql, $singleItemSql];
    }

    /**
     * 临时submission存储
     * @return void
     */
    public function temp(Request $request, $id)
    {
        $submit = $request->all();
        // $request->session()->remove('temp_' . $id . $submit['_token']);
        // $request->session()->put('temp_' . $id . $submit['_token'], $submit);
        Cache::pull('temp_' . $id . $submit['_token']);
        Cache::add('temp_' . $id . $submit['_token'], $submit);
    }

    public function paraphrase($sql, $bindings)
    {
        $sql = str_replace('\?', '********', $sql);
        foreach ($bindings as $binding) {
            if (is_array($binding)) {
                $binding = implode(',', $binding);
                $sql = preg_replace('/\?/', $binding, $sql, 1);
            } else {
                $sql = preg_replace('/\?/', "'" . $binding . "'", $sql, 1);
            }
        }
        $sql = str_replace('********', '?', $sql);
        return $sql;
    }
}
