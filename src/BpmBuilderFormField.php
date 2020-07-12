<?php

namespace Thans\Bpm;

use Dcat\Admin\Form\Field;

class BpmBuilderFormField extends Field
{

    protected $view = 'bpm::builder';

    protected static $css = [
        'vendors/dcat-admin-extensions/bpm/formio.js/formio.full.min.css',
        'vendors/dcat-admin-extensions/bpm/css/formio.custom.css'
    ];
    protected static $js = [
        'vendors/dcat-admin-extensions/bpm/formio.js/formio.full.min.js',
        'vendors/dcat-admin-extensions/bpm/formio.js/language/zh-CN.js'
    ];

    public function render()
    {
        if (empty($this->id)) {
            $this->id = 'form_' . uniqid();
        }
        if (empty($this->value)) {
            $this->value = !empty($this->default) ? $this->default : [];
        }
        if (!is_string($this->value)) {
            $this->value = json_encode($this->value);
        } else {
            $this->value = json_encode(json_decode($this->value));   //兼容json里有类似</p>格式，首次初始化显示会丢失的问题
        }
        $builderId =  'form_' . md5($this->id);
        $name = $this->getElementName();
        $url = str_replace('/form', '', route('bpm.baseurl'));
        $this->script .= <<<EOT

Formio.icons = "fontawesome"
var {$builderId} = Formio.builder(document.getElementById('{$this->id}'), {$this->value}, {
  language: 'zh-CN',
  noDefaultSubmitButton: true,
  i18n: cn,
  baseUrl: '{$url}',
}).then(function (form) {
    form.on('change', function(build) {
        $('input[name="{$name}"]').val(JSON.stringify(form.schema));
    });
    $(".dcat-admin-body").bind("DOMNodeInserted", function(){
        $('.formio-component-tableView').hide();
        $('.formio-component-persistent').hide();
    });
    console.log(form);
});

$('button[type="reset"]').click(function(){
    {$builderId}.form = {};
});
EOT;
        return parent::render();
    }
}
