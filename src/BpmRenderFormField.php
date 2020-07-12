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

    protected $showMode = false;

    protected static $css = [
        'vendors/dcat-admin-extensions/bpm/formio.js/formio.full.min.css',
        'vendors/dcat-admin-extensions/bpm/css/formio.custom.css'
    ];
    protected static $js = [
        'vendors/dcat-admin-extensions/bpm/formio.js/formio.full.min.js',
        'vendors/dcat-admin-extensions/bpm/formio.js/language/zh-CN.js'
    ];

    public function showMode($showMode = false)
    {
        $this->showMode = $showMode;
        return $this;
    }

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
        $components = $this->components;
        $showMode = $this->showMode ? 'readOnly: true,' : '';
        $url = str_replace('/form', '', route('bpm.baseurl'));
        $this->script .= <<<EOT
var submitForm;
var bpmForm = function(form) {
  form.on('change', function(event){
  });
  if({$this->value}.length != 0){
    form.submission = {
        data: {$this->value}
    };
  }
  submitForm = form;
};

Formio.icons = "fontawesome"
var bpmFormBuilder = Formio.createForm(document.getElementById('{$this->id}'), {$components}, {
  language: 'zh-CN',
  name:'data',
  noDefaultSubmitButton: true,
  i18n: cn,
  baseUrl: '{$url}',
  {$showMode}
}).then(bpmForm);

var \$form = \$('#{$this->getFormElementId()}');
\$form.find('.submit').off('click');
\$form.on('submit',function(){
    submitForm.submitForm().then(function(v){
        Dcat.Form({
            form: \$('#{$this->getFormElementId()}'),
        });
    }).catch(function(e) {
    })
    return false;
});
$('button[type="reset"]').click(function(){
    submitForm.submission = {};
    submitForm.triggerRedraw();
});
EOT;
        return parent::render();
    }
}
