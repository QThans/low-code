<?php

namespace Thans\Bpm;

use Dcat\Admin\Form\Field;

class BpmRenderFormField extends Field
{
    protected $view = 'bpm::render';
    /**
     * Form build components
     * @var string
     */
    protected $components;

    protected static $css = [
        'vendors/dcat-admin-extensions/bpm/formio.js/formio.full.min.css'
    ];
    protected static $js = [
        'vendors/dcat-admin-extensions/bpm/formio.js/formio.full.min.js',
        'vendors/dcat-admin-extensions/bpm/formio.js/language/zh-CN.js'
    ];

    public function components($components =  null)
    {
        if (is_array($components) || is_object($components)) {
            $this->components = json_encode($components);
            return $this;
        }
        if ($components == null) {
            return $this->$components;
        }
        if ($components instanceof \Closure) {
            $components = $components($this->label);
        }
        $this->components = $components;
        return $this;
    }

    public function render()
    {
        if (empty($this->id)) {
            $this->id = 'builder_' . uniqid();
        }
        if (empty($this->value)) {
            $this->value = !empty($this->default) ? $this->default : [];
        }
        if (!is_string($this->value)) {
            $this->value = json_encode($this->value);
        } else {
            $this->value = json_encode(json_decode($this->value));   //兼容json里有类似</p>格式，首次初始化显示会丢失的问题
        }
        $alias = 'builder_' . uniqid();
        $components = $this->components;
        $this->script .= <<<EOT

Formio.icons = "fontawesome"
var {$alias} = Formio.createForm(document.getElementById('{$this->id}'), {$components}, {
  language: 'zh-CN',
  noDefaultSubmitButton: true,
  i18n: cn,
}).then(function (form) {
    
});
$('button[type="reset"]').click(function(){
    {$alias}.form = {};
});
EOT;
        return parent::render();
    }
}
