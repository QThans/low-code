<?php

namespace Thans\Bpm;

use Dcat\Admin\Form\Field;
use Illuminate\Support\Facades\Request;

class BpmRenderFormField extends Field
{
    protected $view = 'bpm::render';
    /**
     * Form build components
     * @var string
     */
    protected $components;

    protected $showMode = false;

    protected $saveTips = '';

    /**
     * 表单全部数据
     * @var array
     */
    protected $formData = [];

    protected static $css = [
        'vendors/bpm/formio.js/formio.full.min.css',
        'vendors/bpm/css/formio.custom.css'
    ];
    protected static $js = [
        'vendors/bpm/formio.js/formio.full.min.js',
        'vendors/bpm/formio.js/language/zh-CN.js',
        'vendors/bpm/formio.js/language/flatpickr/zh.js'
    ];

    public function saveTips($saveTips)
    {
        $this->saveTips = $saveTips;
        return $this;
    }

    public function showMode($showMode = false)
    {
        $this->showMode = $showMode;
        return $this;
    }

    public function components($components = null)
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

    public function formData($data)
    {
        $this->formData = $data;
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
        $this->valueId = Request::route('form', 0);
        $formStatus = isset($this->formData['status']) ? $this->formData['status'] : 0;
        $components = $this->components;
        $showMode = $this->showMode ? 'readOnly: true,' : '';
        $url = str_replace('/form', '', route('bpm.baseurl'));
        $this->script .= <<<EOT
window.debug = true;
function deepClone(source){
    const targetObj = source.constructor === Array ? [] : {}; // 判断复制的目标是数组还是对象
    for(let keys in source){ // 遍历目标
        if(source.hasOwnProperty(keys)){
        if(source[keys] && typeof source[keys] === 'object'){ // 如果值是对象，就递归一下
            targetObj[keys] = source[keys].constructor === Array ? [] : {};
            targetObj[keys] = deepClone(source[keys]);
        }else{ // 如果不是，就直接赋值
            targetObj[keys] = source[keys];
        }
        }
    }
    return targetObj;
}
function arrayToObject(arr){
    $.each(arr,function(key,value){
        if(typeof value  == 'object'){
            $.each(value, function (item,e) {
                if(typeof e  == 'object'){
                    var obj = {};
                    for(x in e){
                        obj[x] = e[x];
                    }
                    value[item] = obj;
                }
            })
            arrayToObject(value);
        }
    });
    return arr;
}
var submitForm;
var initFunc = false;
var value = {$this->value};
window.id = {$this->valueId};
window.status = {$formStatus};
var bpmFormThen = function(form) {
  form.on('change', function(event){
    var data = deepClone(form.data);
    $('input[name="data"]').val(JSON.stringify(arrayToObject(data)));
    if(typeof window.initMultiple != "undefined" && initFunc == false){
        initFunc = true;
        window.initMultiple();
    }
  });
  if(value.length != 0){
    form.submission = {
        data: value
    };
  }
  submitForm = form;
};

Formio.icons = "fontawesome"
var components = $components;
var bpmForm = Formio.createForm(document.getElementById('{$this->id}'), components, {
  language: 'zh-CN',
  name:'data',
  noDefaultSubmitButton: true,
  i18n: cn,
  baseUrl: '{$url}',
}).then(bpmFormThen);
var \$form = \$('#{$this->getFormElementId()}');
\$form.find('.submit').off('click');
var disabledElements = $(":disabled");
function submitScatForm(){
    Dcat.Form({
        form: \$('#{$this->getFormElementId()}'),
        after:function(){
            $('#areaSelect').attr("disabled",true);
            $(disabledElements).attr('disabled','true');
        }
    });
}
function submit(){
    $(disabledElements).removeAttr('disabled');
    if(!'{$this->saveTips}'){
        submitScatForm();
        return false;
    }
    Dcat.confirm('您确定要提交表单吗？', '{$this->saveTips}', function () {
        submitScatForm();
    });
}
\$form.on('submit',function(){
    if(typeof submitForm.data['delete'] != 'undefined' && submitForm.data['delete'] != ''){
        submit();
        return;
    }
    if($("input[name='save-status']").is(':checked')){
        submit();
        return;
    }
    submitForm.submitForm().then(function(v){
        submit();
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
