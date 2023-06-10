<?php

namespace Thans\Bpm\Grid\Components;

class TextGrid extends FieldGrid
{
    public function render()
    {
        return $this->column;
    }
    public function export()
    {
        return isset($this->column['submission'][$this->field['name']]) ? $this->column['submission'][$this->field['name']] : null;
    }
}
