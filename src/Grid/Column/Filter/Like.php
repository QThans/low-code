<?php

namespace Thans\Bpm\Grid\Column\Filter;

use Dcat\Admin\Grid\Column\Filter\Equal;
use Dcat\Admin\Grid\Model;
use Thans\Bpm\Grid\Column\Filter\Traits\NoSupport;

class Like extends Equal
{
    use NoSupport;

    /**
     * Add a binding to the query.
     *
     * @param string $value
     * @param Model|null $model
     */
    public function addBinding($value, Model $model)
    {
        $this->check();
        $value = trim($value);
        if ($value === '') {
            return;
        }
        $name = strpos($this->getOriginalColumnName(), 'submission.') === false ? $this->getOriginalColumnName() : str_replace('submission.', '"form_submissions"."submission"->>\'', $this->getOriginalColumnName() . "'");
        if (strpos($name, 'submission') === false) {
            $this->withQuery($model, 'where', ['like', "%{$value}%"]);
        }else{
            $model->whereRaw($name . ' like ' . "'%{$value}%'");
        }
    }
}
