<?php

namespace Thans\Bpm\Grid\Column\Filter;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid\Column\Filter;
use Dcat\Admin\Grid\Model;
use Dcat\Admin\Grid\Column\Filter\Between as DcatBetween;

class Between extends DcatBetween
{
    /**
     * Add a binding to the query.
     *
     * @param string $value
     * @param Model|null $model
     */
    public function addBinding($value, Model $model)
    {
        $value = array_filter((array) $value);
        $name = strpos($this->getOriginalColumnName(), 'submission.') === false ? $this->getOriginalColumnName() : str_replace('submission.', '"form_submissions"."submission"->>\'', $this->getOriginalColumnName() . "'");

        if (empty($value)) {
            return;
        }

        if ($this->timestamp) {
            $value = array_map(function ($v) {
                if ($v) {
                    return strtotime($v);
                }
            }, $value);
        }

        if (! isset($value['start']) || $value['start'] == '') {
            $model->whereRaw($name . ' <= \'' . $value['end']."'");

            return;
        }

        if (! isset($value['end']) || $value['end'] == '') {
            $model->whereRaw($name . ' >= \'' . $value['start']."'");
            return;
        }
        $model->whereRaw($name . ' between \'' . $value['start']."' and '".$value['end']."'");
        // $this->withQuery($model, 'whereBetween', [array_values($value)]);
//
//        // $model->where($this->getColumnName(), $value);
    }



    public function  valueFilter()
    {
        return $this;
    }
}
