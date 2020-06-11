<?php

namespace Thans\Bpm;

use Dcat\Admin\Extension;
use Illuminate\Console\Command;

class Bpm extends Extension
{
    const NAME = 'bpm';

    /**
     * 版本号.
     *
     * @var string
     */
    const VERSION = '1.0.0-dev';

    public $serviceProvider = BpmServiceProvider::class;

    public $composer = __DIR__ . '/../composer.json';

    public $assets = __DIR__ . '/../resources/assets';

    public $views = __DIR__ . '/../resources/views';

    // protected $lang = __DIR__.'/../resources/lang';

    // public $menu = [
    //     'title' => 'Bpm',
    //     'path'  => 'bpm',
    //     'icon'  => 'fa-cubes',
    // ];
    /**
     * 版本.
     *
     * @return string
     */
    public static function longVersion()
    {
        return sprintf('Bpm <comment>version</comment> <info>%s</info>', static::VERSION);
    }
}
