<?php

namespace Larawos\Generators\Command;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Larawos\Generators\Support\Helper;

class ModuleMakeCommands extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'larawos:module';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new module with a lot of files.';

    /**
     * The helps instance.
     *
     * @var Helper
     */
    protected $helps;

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     * @param Composer $composer
     */
    public function __construct(Helper $helps)
    {
        parent::__construct();

        $this->helps = $helps;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->helps->setModule($this->argument('name'));

        $components = collect($this->helps->getComponents())->keys();

        if ($only = $this->option('only')) {
            $onlys = $this->parseOption($only);
            $components = $components->intersect($onlys);
        } elseif ($except = $this->option('except')) {
            $excepts = $this->parseOption($except);
            $components = $components->diff($excepts);
        }

        foreach ($components as $component) {
            if ($this->helps->make($component)) {
                $this->info(camel_case($component) . ' created successfully.');
            }
        }

        if ($this->helps->optimize()) {
            $this->info(camel_case($this->argument('name')) . ' optimized successfully.');
        }
    }

    /**
     * parse the console command options.
     *
     * @return array
     */
    protected function parseOption($option)
    {
        return empty(explode(',', $option)) ? [] : explode(',', $option);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the module'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['except', 'e', InputOption::VALUE_OPTIONAL, 'Get all of the given array except for a specified array of items.', null],
            ['only', 'o', InputOption::VALUE_OPTIONAL, 'Get a subset of the items from the given array.', null]
        ];
    }

}
