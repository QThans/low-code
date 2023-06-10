<?php

namespace Thans\Bpm\Helpers;

use Illuminate\Support\Facades\Redis;

class Component
{
    protected static $fields;

    /**
     * 列表上要展开显示的组件
     * @var string[]
     */
    protected static $expandKey = ['editgrid', 'address', 'datagrid'];

    protected static $treeKey = ['container', 'editgrid', 'address', 'datagrid', 'chinaCity'];

    protected static $noTreeKey = ['panel', 'table', 'well', 'columns', 'fieldset', 'tabs', 'form'];

    protected static $systemFields = [
        'id' => ['label' => 'ID'],
        'createdUser.id' => ['label' => '创建账号ID'],
        'createdUser.name' => ['label' => '创建账号名称'],
        'createdUser.username' => ['label' => '创建账号用户名'],
        'user.id' => ['label' => '所属账号ID'],
        'user.name' => ['label' => '所属账号名称'],
        'user.username' => ['label' => '所属账号用户名'],
        'updateduser.id' => ['label' => '更新账号ID'],
        'updateduser.name' => ['label' => '更新账号名称'],
        'updateduser.username' => ['label' => '更新账号用户名'],
        'created_at' => ['label' => '创建时间'],
        'updated_at' => ['label' => '修改时间'],
    ];

    public static function isSystemField($field)
    {
        return isset(self::$systemFields[$field]) ? self::$systemFields[$field] : false;
    }

    public static function getSystemFields()
    {
        return self::$systemFields;
    }

    public static function getExpandKey()
    {
        return self::$expandKey;
    }

    public static function getTreeKey()
    {
        return self::$treeKey;
    }

    public static function setFields($fields)
    {
        self::$fields = $fields;
    }

    public static function eachComponents($components, $path = '', $callable = null)
    {
        if (!is_array($components)) {
            return false;
        }
        foreach ($components as $key => $value) {
            $key = $path ? $path . '.' : '';
            if (!isset($value['type']) && isset($value['components'])) {
                self::eachComponents($value['components'], '', $callable);
                continue;
            }
            if (!isset($value['type']) && !isset($value['components'])) {
                self::eachComponents($value, '', $callable);
                continue;
            }
            if (in_array($value['type'], self::$treeKey)) {
                //作为父级添加到fields
                self::$fields[$key . $value['key']] = $value;
                //需要增加path并循环
                self::eachComponents($value['components'], $key . $value['key'], $callable);
                continue;
            }
            if (in_array($value['type'], self::$noTreeKey)) {
                //需要增加循环，不增加path
                if (isset($value['components'])) {
                    self::eachComponents($value['components'], '', $callable);
                }
                if (isset($value['rows'])) {
                    self::eachComponents($value['rows'], '', $callable);
                }
                if (isset($value['columns'])) {
                    self::eachComponents($value['columns'], '', $callable);
                }
                continue;
            }
            if (!is_null($callable)) {
                $callable($key . $value['key'], $value);
            }
            self::$fields[$key . $value['key']] = $value;
        }
        return self::$fields;
    }
}
