<?php

namespace Thans\Bpm\Grid\Column\Filter;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid\Column\Filter;
use Dcat\Admin\Grid\Model;
use Dcat\Admin\Grid\Column\Filter\Equal as DcatEqual;

class Equal extends DcatEqual
{
    /**
     * Add a binding to the query.
     *
     * @param string $value
     * @param Model|null $model
     */
    public function addBinding($value, Model $model)
    {
        $value = trim($value);
        if ($value === '') {
            return;
        }
        // $model->where($this->getColumnName(), $value);
        $name = strpos($this->getOriginalColumnName(), 'submission.') === false ? $this->getOriginalColumnName() : str_replace('submission.', '"form_submissions"."submission"->>\'', $this->getOriginalColumnName() . "'");
        if (strpos($name, 'submission') === false) {
            $this->withQuery($model, 'where', [$value]);
        }else{
            $model->whereRaw($name . ' = ' . "'{$value}'");
        }
    }
}
