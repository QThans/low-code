<?php

namespace Thans\Bpm\Syntax;

use Dcat\Admin\Admin;
use Illuminate\Support\Facades\DB;
use Thans\Bpm\Models\User;

class Query
{

    protected $options = [
        'allowFunc' => [], //允许解析SQL函数，设置了以后不在允许的或者在禁止的都无法解析
        'banFunc' => [], //禁止解析SQL函数
    ];

    public static $selectAllowSqlArray = ['subTableJoin', 'select', 'selectRaw'];

    public function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    public function isExcuMethod($method)
    {
        if ($this->options['allowFunc'] == [] && $this->options['banFunc'] == []) {
            return true;
        }
        if (in_array($method, $this->options['banFunc'])) {
            return false;
        }
        if (is_array($this->options['allowFunc']) && count($this->options['allowFunc'])  != 0 && !in_array($method, $this->options['allowFunc'])) {
            return false;
        }
        return true;
    }

    protected $subSqlMethods = [
        'subTableJoin'
    ];

    protected $need2Raw = [
        'join'
    ];

    /**
     * @param $query
     * @param $formData
     * @param $formSubmission
     * @param false $strictmodel 严格模式情况下，对于解析失败的语句不进行强制解析。
     * @param array $subSqls 只针对子表执行，并且最后执行的语句
     * @return mixed
     */
    public function render($query, $formData, $formSubmission, $strictModel = false, &$subSqls = [])
    {
        $methods = $this->analyse($query, $formData, $strictModel);
        foreach ($methods as $value) {
            $method = $value['method'];
            //是否在允许内
            if (is_array($this->options['allowFunc']) && count($this->options['allowFunc'])  != 0 && !in_array($method, $this->options['allowFunc'])) {
                //不在允许范围内，并且设置了allowFunc，该函数无法使用。
                continue;
            }
            //禁止解析的函数
            if (is_array($this->options['allowFunc']) && in_array($method, $this->options['banFunc'])) {
                continue;
            }
            if (in_array($method, $this->subSqlMethods)) {
                $subSqls[] =  $value;
                continue;
            }
            // use DB::raw
            if (in_array($method, $this->need2Raw)) {
                foreach ($value['params'] as $pk => $pv) {
                    $value['params'][$pk] = DB::raw($pv);
                }
            }
            switch (count($value['params'])) {
                case 1:
                    $formSubmission = $formSubmission->$method($value['params'][0]);
                    break;
                case 2:
                    $formSubmission = $formSubmission->$method($value['params'][0], $value['params'][1]);
                    break;
                case 3:
                    $formSubmission = $formSubmission->$method($value['params'][0], $value['params'][1], $value['params'][2]);
                    break;
                case 4:
                    $formSubmission = $formSubmission->$method($value['params'][0], $value['params'][1], $value['params'][2], $value['params'][3]);
                    break;
            }
        }
        return $formSubmission;
    }

    public function analyse($query, $formData, $strictModel)
    {
        $syntax = [];
        $isMatched = preg_match_all('/(.*?)\((.*?)\);/', trim($query), $matches);
        if ($isMatched && count($matches) == 3) {
            for ($i = 0; $i < count($matches[0]); ++$i) {
                $params = str_replace('\,', '******', $matches[2][$i]);
                $params = explode(',', $params);
                foreach ($params as $key => $value) {
                    $value = substr($value, 0, strlen($value) - 1);
                    $params[$key] = substr($value, 1);
                    $params[$key] = str_replace('******', ',', $params[$key]);
                }
                $syntax[] = ['method' => $matches[1][$i], 'params' => $params];
            }
        }
        $methods = [];
        foreach ($syntax as $key => $value) {
            $method = trim($value['method']);
            //针对一定情况下，对应语句放弃解析，不进行查询。
            $merge = true;
            foreach ($value['params'] as $k => $v) {
                $isEval = preg_match('/\@【(.*?)】/', $v, $matches);
                if ($isEval) {
                    $code = str_replace('\;', ';', $matches[1]);
                    // $value['params'][$k] = 'isset($user->$index) ? (string)$user->$index : (string)$index';
                    $v = $value['params'][$k] = str_replace($matches[0], eval($code), $v);
                }
                $isVar = preg_match_all('/【(.*?)】/', $v, $matches);
                if ($isVar) {
                    foreach ($matches[1] as $key => $matche) {
                        if ($strictModel && !isset($formData->$matche)) {
                            $merge = false;
                            break;
                        }
                        if (isset($formData->$matche)) {
                            $data = $formData->$matche;
                            if (is_array($data)) {
                                $data = implode(',', $data);
                            }
                        }
                        $v = isset($formData->$matche) ? str_replace($matches[0][$key], $data, $v) : $v;
                    }
                    $value['params'][$k] = $v;
                    //如果值空，格式完善
                    if ($value['params'][$k] == '') {
                        $value['params'][$k] = "''";
                    }
                }
                $user = Admin::user();
                $isUser = preg_match('/\$【(.*?)】/', $v, $matches);
                if ($isUser) {
                    $index = $matches[1];
                    if ($strictModel && !isset($user->$index)) {
                        $merge = false;
                        break;
                    }
                    $value['params'][$k] = isset($user->$index) ? (string)$user->$index : (string)$index;
                }
                $userModel = User::where('id', $user->id)->with(['departments'])->first();
                $departments = [];
                $isDepartment = preg_match('/\#【(.*?)】/', $v, $matches);
                if ($isDepartment) {
                    $index = $matches[1];
                    foreach (isset($userModel->toArray()['departments']) ? $userModel->toArray()['departments'] : [] as $department) {
                        $departments[] = $department[$index];
                    }
                    // $value['params'][$k] = 'isset($user->$index) ? (string)$user->$index : (string)$index';
                    $value['params'][$k] = str_replace($matches[0], implode(',', $departments), $v);
                }
            }
            if ($merge) {
                $methods[] = $value;
            }
        }
        return $methods;
    }
    /**
     * @param $sql 原语句
     * @param $joinSql 要join的语句
     * @return mixed
     */
    public function subTableJoin($sql, $value)
    {
        $subSql = isset($value['params'][0]) ? $value['params'][0] : '';
        return $sql . ' ' . $subSql;
    }
}
