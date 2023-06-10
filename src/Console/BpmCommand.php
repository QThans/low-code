<?php

namespace Thans\Bpm\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Thans\Bpm\Bpm;
use Illuminate\Support\Str;

class BpmCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bpm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all bpm commands';
    /**
     * @var string
     */
    public static $logo = <<<LOGO
    __
    /\ \
    \ \ \____  _____     ___ ___
     \ \ '__`\/\ '__`\ /' __` __`\
      \ \ \L\ \ \ \L\ \/\ \/\ \/\ \
       \ \_,__/\ \ ,__/\ \_\ \_\ \_\
        \/___/  \ \ \/  \/_/\/_/\/_/
                 \ \_\
                  \/_/
LOGO;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->line(static::$logo);
        $this->line(Bpm::longVersion());
        $this->comment('');
        $this->comment('Available commands:');
        $this->listAdminCommands();
    }

    /**
     * List all bpm commands.
     *
     * @return void
     */
    protected function listAdminCommands()
    {
        $commands = collect(Artisan::all())->mapWithKeys(function ($command, $key) {
            if (Str::startsWith($key, 'bpm:')) {
                return [$key => $command];
            }

            return [];
        })->toArray();

        $width = $this->getColumnWidth($commands);

        /** @var Command $command */
        foreach ($commands as $command) {
            $this->line(sprintf(" %-{$width}s %s", $command->getName(), $command->getDescription()));
        }
    }
    /**
     * @param (Command|string)[] $commands
     *
     * @return int
     */
    private function getColumnWidth(array $commands)
    {
        $widths = [];

        foreach ($commands as $command) {
            $widths[] = static::strlen($command->getName());
            foreach ($command->getAliases() as $alias) {
                $widths[] = static::strlen($alias);
            }
        }

        return $widths ? max($widths) + 2 : 0;
    }
    /**
     * Returns the length of a string, using mb_strwidth if it is available.
     *
     * @param string $string The string to check its length
     *
     * @return int The length of the string
     */
    public static function strlen($string)
    {
        if (false === $encoding = mb_detect_encoding($string, null, true)) {
            return strlen($string);
        }

        return mb_strwidth($string, $encoding);
    }
}
