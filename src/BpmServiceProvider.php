<?php

namespace Thans\Bpm;

use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Illuminate\Support\ServiceProvider;

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
