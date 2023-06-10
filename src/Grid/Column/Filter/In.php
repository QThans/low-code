<?php

namespace Thans\Bpm\Grid\Column\Filter;

use Dcat\Admin\Grid\Column\Filter;
use Dcat\Admin\Grid\Column\Filter\Checkbox;
use Dcat\Admin\Grid\Model;

class In extends Filter
{
    use Checkbox;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * CheckFilter constructor.
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;

        $this->class = [
            'all'  => uniqid('column-filter-all-'),
            'item' => uniqid('column-filter-item-'),
        ];
    }

    /**
     * Add a binding to the query.
     *
     * @param array $value
     * @param Model $model
     */
    public function addBinding($value, Model $model)
    {
        if (empty($value)) {
            return;
        }
        for ($i = 0; $i <  count($value); $i++) {
            $value[$i] =  "'" . $value[$i] . "'";
        }
        $value = implode(',', $value);
        $name = strpos($this->getOriginalColumnName(), 'submission.') === false ? $this->getOriginalColumnName() : str_replace('submission.', '"form_submissions"."submission"->>\'', $this->getOriginalColumnName() . "'");
        if (strpos($name, 'submission') === false) {
            $this->withQuery($model, 'whereIn', [$value]);
        }else{
            $model->whereRaw($name . ' in ' . "({$value})");
        }
    }

    /**
     * Render this filter.
     *
     * @return string
     */
    public function render()
    {
        return $this->renderCheckbox();
    }

    public function  valueFilter()
    {
        return $this;
    }
}
