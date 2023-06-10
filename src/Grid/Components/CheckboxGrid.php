<?php

namespace Thans\Bpm\Grid\Components;

use Thans\Bpm\Http\Controllers\FormController;
use Thans\Bpm\Models\Form;

class CheckboxGrid extends FieldGrid
{
    public function render()
    {
        if ($this->column == 1) {
            return $this->options['label'];
        }
    }
    public function export()
    {
        $value = isset($this->column['submission'][$this->field['name']]) ? $this->column['submission'][$this->field['name']] : null;
        if ($value) {
            return $this->options['label'];
        }
    }
}
