<?php

namespace Thans\Bpm\Grid\Js;

use Dcat\Admin\Admin;
use Thans\Bpm\Http\Controllers\BpmController;
use Thans\Bpm\Models\Form;

class MultipleResource
{
    public static function render()
    {
        Admin::js('@resource-selector');
        self::js();
    }
    public static function js()
    {
        Admin::js('vendors/bpm/js/js.cookie.min.js');
        Admin::js('vendors/bpm/js/base64.min.js');
        $forms = Form::get()->pluck('alias', 'id');
        // $tempUrl = route('bpm.formTemp', ['id' => '"+window.formId+"']);
        // $tempUrl = str_replace('%22', '"', $tempUrl);
        $queryName = BpmController::RESOURCE_QUERY_NAME;
        $js = <<<JS
        window.initMultiple = function(){
            var form = Object.values(Formio.forms)[Object.values(Formio.forms).length - 1];
            window.forms = {$forms};
            $.each($('i[class*="formio-button-multi-row-"]'),function(){
                $(this).off('click');
                // Cookies.set('query_'+$(this).data('key'),Base64.encode(encodeURIComponent(form.getComponent($(this).data('datagridkey')+'.'+$(this).data('key'))[0].component.dataFiltering)));
                Dcat.ResourceSelector({
                    title: $(this).data('title'),
                    column: $(this).data('key'),
                    source: '/admin/bpm/'+ forms[$(this).data('resource')] +'/form?form='+window.formId+'&path='+$(this).data('datagridkey')+'.'+$(this).data('key')+'&token='+window.requestKey+'&datagrid='+$(this).data('datagrid'),
                    selector: $(this),
                    maxItem: 0,
                    area: ["65%","65%"],
                    queryName: '&{$queryName}',
                    items: {},
                    placeholder: $(this).html(),
                    showCloseButton: true,
                    closeButtonText:'确定',
                    // disabled: '',
                    displayer: function(tag, input, options){
                        $('.layui-layer-btn0').off('click');
                        if (Dcat.helpers.len(tag)) {
                            window.initMultiple();
                        }
                        $('iframe[src="'+options.source+'&{$queryName}=1"]').last().parents('.layui-layer-iframe').find('.layui-layer-btn0').off('click');
                        $('iframe[src="'+options.source+'&{$queryName}=1"]').last().parents('.layui-layer-iframe').find('.layui-layer-btn0').click(function(){
                            var datagridKey = $(input).data('datagridkey');
                            var placeKey = $(input).data('key');
                            var originalLength = form.getComponent(datagridKey).dataValue.length;
                            console.log(originalLength)
                            $.each(tag,function(index,value){
                                if(originalLength != 1){
                                    form.getComponent(datagridKey).addRow();
                                }else{
                                    // form.getComponent(datagridKey).removeRow(0);
                                }
                                originalLength = 0;
                                form.getComponent(datagridKey).dataValue[form.getComponent(datagridKey).dataValue.length-1][placeKey] = index;//value是现实的内容，index是ID
                            });
                            form.getComponent(datagridKey).setValue(form.getComponent(datagridKey).dataValue);
                            if(typeof form.getComponent(datagridKey).dataValue[0] != 'undefined'){
                                form.getComponent(datagridKey+'.'+placeKey)[0].updateItems(null,true);
                            }
                            $.each(form.getComponent(datagridKey+'.'+placeKey),function(k,v){
                                v.update();
                            });
                            window.layer.closeAll();
                        });
                    },
                    displayerContainer: $(this),
                });
                $('.formio-button-multi-row .default-text').css('opacity',1);
            });
            $('.formio-button-multi-row').off('click');

        }
JS;
     // $('.formio-button-multi-row').click(function(){
            //     var clickKey = $(this).data('key');
            //     var submit = form.data;
            //     submit['_token'] = Dcat.token;
            //     $.post("{$tempUrl}",submit,function(result){
            //         $('.formio-button-multi-row-' + clickKey).click();
            //         return false;
            //     });
            // });
        Admin::script($js);
    }
}
