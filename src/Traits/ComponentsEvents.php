<?php

namespace Thans\Bpm\Traits;

use Thans\Bpm\DocumentNoGenerator;
use Dcat\Admin\Form;

trait ComponentsEvents
{
    public function eventsHandle($form)
    {
        foreach ($this->formComponents['components'] as $key => $component) {
            if (method_exists($this, $component['type'])) {
                $method = $component['type'];
                $this->$method($form, $component, $key);
            }
        }
    }
    public function textfield($form, $component, $key)
    {
        if (isset($component['documentNo']) && $component['documentNo']) {
            $this->documentId($form, $component, $key);
        }
    }

    public function radio($form, $component, $key)
    {
        $form->saving(function (Form $form) use ($component) {
            $this->submission[$component['key']] = array_values($this->submission[$component['key']])[0];
        });
    }

    protected function documentId($form, $component, $key)
    {
        if ($this->showMode || $form->isEditing()) {
            $this->formComponents['components'][$key]['prefix'] = '';
            $this->formComponents['components'][$key]['suffix'] = '';
        }
        $form->saving(function (Form $form) use ($component) {
            if ($form->isCreating()) {
                $prefix = isset($component['prefix']) ? $component['prefix'] : '';
                $suffix = isset($component['suffix']) ? $component['suffix'] : '';
                $this->submission[$component['key']] = DocumentNoGenerator::generate($this->formData['alias'], $component['documentNo'], $prefix, $suffix);
            }
            if ($form->isEditing()) {
                $this->submission[$component['key']] = $form->model()->submission[$component['key']];
            }
        });
    }
}
