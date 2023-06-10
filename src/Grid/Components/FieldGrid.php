<?php

namespace Thans\Bpm\Grid\Components;

use Illuminate\Contracts\Support\Renderable;

class FieldGrid implements Renderable
{
    protected $options;

    protected $field;

    protected $column;
    
    protected $object;

    public function __construct($column, $field, $options, $object)
    {
        $this->options = $options;
        $this->column = $column;
        $this->field = $field;
        $this->object = $object;
    }
    public function render()
    {
    }
    public function export()
    {
    }
}
