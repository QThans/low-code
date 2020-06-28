<?php

namespace Thans\Bpm\Traits;

trait ChildrenManage
{
    protected static $childrenId = [];
    public  function buildChildrenId($nodes = null, $needPid = 0, $p = 0)
    {
        if (!$nodes) {
            $nodes = $this->toTree($nodes);
            self::$childrenId = [];
        }
        foreach ($nodes as $key => $value) {
            if (!isset(self::$childrenId[$value['id']])) {
                if ($value['id'] == $needPid || $needPid == 0) {
                    self::$childrenId[$value['id']][] = $value['id'];
                }
            }
            if ($value['parent_id'] == 0) {
                $p = $value['id'];
            } else {
                if ($p != $value['id']) {
                    if ($p == $needPid || $needPid == 0) {
                        self::$childrenId[$p][] = $value['id'];
                    }
                }
                if (isset($value['children'])) {
                    $this->buildChildrenId($value['children'], $needPid, $value['id']);
                }
            }
            if (isset($value['children'])) {
                $this->buildChildrenId($value['children'], $needPid, $p);
            }
        }
        return self::$childrenId;
    }
    public static function childrenIds(\Closure $closure = null)
    {
        $options = (new static())->withQuery($closure)->buildChildrenId();

        return collect($options)->all();
    }
}
