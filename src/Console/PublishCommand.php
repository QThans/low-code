<?php

namespace Thans\Bpm\Console;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'bpm:publish
    {--force : Overwrite any existing files}
    {--assets : Publish assets files}
    {--migrations : Publish migrations files}
    {--config : Publish configuration files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Re-publish bpm's assets, configuration, language and migration files. If you want overwrite the existing files, you can add the `--force` option";

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $options = ['--provider' => 'Thans\Bpm\BpmServiceProvider'];

        if ($this->option('force')) {
            $options['--force'] = true;
        }

        if ($this->option('migrations')) {
            $options['--tag'] = 'bpm-migrations';
        } elseif ($this->option('config')) {
            $options['--tag'] = 'bpm-config';
        }elseif ($this->option('assets')) {
            $options['--tag'] = 'bpm-assets';
        }
        //  elseif ($this->option('config')) {
        //     $options['--tag'] = 'bpm-config';
        // }

        $this->call('vendor:publish', $options);
        $this->call('view:clear');
    }
}
