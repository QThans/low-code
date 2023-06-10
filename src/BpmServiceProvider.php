<?php

namespace Thans\Bpm;

use AdminSection;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid\Tools\QuickSearch;
use Illuminate\Support\ServiceProvider;
use Thans\Bpm\Models\FormSubmission;
use Thans\Bpm\Observers\FormSubmissionObserver;
use Dcat\Admin\Show\Field;
use EasyDingTalk\Application;
use Illuminate\Support\Facades\Route;
use Thans\Bpm\Http\Controllers\CityDataController;
use Thans\Bpm\Http\Controllers\FormController;
use Thans\Bpm\Models\Apps;
use Thans\Bpm\Observers\AppsObserver;
use Thans\Bpm\Models\Form as ModelForm;
use Thans\Bpm\Observers\FormsObserver;
use Thans\Bpm\Grid\Js\MultipleResource;

class BpmServiceProvider extends ServiceProvider
{
    protected $commands = [
        Console\BpmCommand::class,
        Console\InstallCommand::class,
        Console\PublishCommand::class,
    ];
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $bpm = new Bpm();

        if ($bpm->views) {
            $this->loadViewsFrom($bpm->views, Bpm::NAME);
        }

        if ($bpm->lang) {
            $this->loadTranslationsFrom($bpm->lang, Bpm::NAME);
        }


        $this->app->booted(function () use ($bpm) {
            Admin::app()->routes(function ($router){
                $attributes = array_merge(
                    [
                        'prefix'     => config('admin.route.prefix'),
                        'middleware' => config('admin.route.middleware'),
                    ],
                );
                $router->group($attributes, __DIR__ . '/../routes/web.php');
            });
            $this->routes();
        });

        if ($this->app->runningInConsole() && $assets = $bpm->assets) {
            $this->publishResource();
        }

        Admin::booting(function () {
            Admin::js('vendors/bpm/js/moment.min.js');
            Admin::js('vendors/bpm/js/hashes.js');
            Admin::js('vendors/bpm/js/viewer.js');
            Admin::js('vendors/bpm/js/jquery-viewer.js');
            Admin::css('vendors/bpm/css/viewer.css');
            Form::extend('bpmFormBuilder', BpmBuilderFormField::class);
            Form::extend('bpmFormRender', BpmRenderFormField::class);
            Field::extend('bpmFormShow', FormFieldShow::class);
            FormSubmission::observe(FormSubmissionObserver::class);
            Apps::observe(AppsObserver::class);
            ModelForm::observe(FormsObserver::class);
            Admin::style(
                <<<EOD
            .viewer-container{
                z-index: 999;
            }
            .text-white{
                color:white;
            }
EOD
            );
            Admin::script(<<<EOD
            window.requestKey = Dcat.token;
            $('.main-footer').remove();
            window.Hashes = Hashes;
            //预览文件JS
            $('body').on('click','.filePreview',function(e){
                e.stopPropagation();
                e.stopImmediatePropagation();
                var pattern = /\.(png|jpe?g|gif|svg)(\?.*)?$/g;
                if(pattern.test($(this).data('href'))){
                    layer.open({
                        type: 1,
                        title: false,
                        shadeClose: true,
                        closeBtn:0,
                        shade: 0.2,
                        maxmin: false, //开启最大化最小化按钮
                        area: ['60%', '80%'],
                        content: '<div style="width:100%;height:100%;text-align:center;"><img id="image-preview" style="display:none;max-width:100%;" src="'+$(this).data('href')+'"></div>'
                    });
                    var image = $('#image-preview');
                    image.viewer({
                        inline: true,
                        title: [4,(image, imageData) => $(this).html()+` (\${imageData.naturalWidth} × \${imageData.naturalHeight})`],
                        backdrop: true,
                        toggleOnDblclick: true,
                        toolbar: {
                            zoomIn: 2,
                            zoomOut: 2,
                            reset: 4,
                            rotateLeft: 4,
                            rotateRight: 4,
                            flipHorizontal: 4,
                            flipVertical: 4,
                        },
                    });
                }else{
                    window.open($(this).data('href'), '_blank');
                }
                return false;
            });
EOD);
            Admin::style(<<<CSS
.complex-headers.custom-data-table.dataTable tbody td, .complex-headers.data-thumb-view.dataTable tbody td{
    height:23px;
}
.filePreview{
    color:#495abf !important;
    cursor: pointer;
}
CSS);

            MultipleResource::render();

            admin_inject_section(AdminSection::NAVBAR_USER_PANEL, function () {
                return view('admin::partials.navbar-user-panel', ['user' => Admin::user()]);
            });
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands($this->commands);
        $this->app->singleton('dingtalk', function () {
            $config = config('dingtalk');
            foreach ($config['oauth'] as $key => $value) {
                $config['oauth'][$key]['redirect'] = route($value['redirect']);
            }
            $app = new Application($config);
            return $app;
        });
    }
    protected function routes()
    {
        Route::group([
            'prefix' => 'admin/bpm',
        ], function () {
            Route::get('/cityData/province', [CityDataController::class, 'province']);
            Route::get('/cityData/city', [CityDataController::class, 'city']);
            Route::get('/cityData/area', [CityDataController::class, 'area']);
        });
        require(__DIR__ . '/../routes/public.php');
    }
    protected function publishResource()
    {
        $this->publishes([__DIR__ . '/../config' => config_path()], 'bpm-config');
        $this->publishes([__DIR__ . '/../database/migrations' => database_path('migrations')], 'bpm-migrations');
        $this->publishes([__DIR__ . '/../resources/assets' => public_path('vendors/' . Bpm::NAME)], 'bpm-assets');
        // $this->publishes([__DIR__ . '/../database/seeds' => database_path('seeds')], 'bpm-seeds');
        $this->publishes([__DIR__ . '/../resources/lang' => resource_path('lang')], 'bpm-lang');
    }
}
