<?php

namespace Thans\Bpm;

use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Illuminate\Support\ServiceProvider;
use Thans\Bpm\Models\FormSubmission;
use Thans\Bpm\Observers\FormSubmissionObserver;
use Dcat\Admin\Show\Field;
use Illuminate\Support\Facades\Route;
use Thans\Bpm\Http\Controllers\CityDataController;
use Thans\Bpm\Http\Controllers\FormController;
use Thans\Bpm\Models\Apps;
use Thans\Bpm\Observers\AppsObserver;
use Thans\Bpm\Models\Form as ModelForm;
use Thans\Bpm\Observers\FormsObserver;

class BpmServiceProvider extends ServiceProvider
{
    protected $commands = [
        Console\BpmCommand::class,
    ];
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $extension = Bpm::make();

        if ($views = $extension->views()) {
            $this->loadViewsFrom($views, Bpm::NAME);
        }

        if ($lang = $extension->lang()) {
            $this->loadTranslationsFrom($lang, Bpm::NAME);
        }

        if ($migrations = $extension->migrations()) {
            $this->loadMigrationsFrom($migrations);
        }

        $this->app->booted(function () use ($extension) {
            $extension->routes(__DIR__ . '/../routes/web.php');
        });

        if ($this->app->runningInConsole() && $assets = $extension->assets()) {
            $this->publishResource();
        }

        Admin::booting(function () {
            // Admin::js('vendors/dcat-admin-extensions/bpm/formio.js/formio.js/formio.full.min.js');
            // Admin::js('vendors/dcat-admin-extensions/bpm/formio.js/formio.full.min.css');
            Form::extend('bpmFormBuilder', BpmBuilderFormField::class);
            Form::extend('bpmFormRender', BpmRenderFormField::class);
            Field::extend('bpmFormShow', FormFieldShow::class);
            FormSubmission::observe(FormSubmissionObserver::class);
            Apps::observe(AppsObserver::class);
            ModelForm::observe(FormsObserver::class);
            Admin::script(<<<EOD
            $('.main-footer').remove();
EOD);
        });
        // $this->registerPublishing();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        Route::get('/admin/bpm/cityData/province', [CityDataController::class, 'province']);
        Route::get('/admin/bpm/cityData/city', [CityDataController::class, 'city']);
        Route::get('/admin/bpm/cityData/area', [CityDataController::class, 'area']);
        $this->commands($this->commands);
    }
    protected function publishResource()
    {
        $this->publishes([__DIR__ . '/../config' => config_path()], 'bpm-config');
        $this->publishes([__DIR__ . '/../database/migrations' => database_path('migrations')], 'bpm-migrations');
        $this->publishes([__DIR__ . '/../database/seeds' => database_path('seeds')], 'bpm-seeds');
        // $this->publishes([__DIR__ . '/../resources/lang' => resource_path('lang')], 'bpm-lang');
    }
}
