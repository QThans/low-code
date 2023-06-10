<?php

namespace Thans\Bpm\Grid\Column\Filter\Traits;

trait NoSupport
{
    public function check()
    {
        if ($this->getColumnName() == 'id') {
            admin_error('配置错误', 'ID字段列过滤类型只能设置精准匹配');
            return;
        }
    }
}
