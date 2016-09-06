<?php 

namespace Larawos\Generators\Support;

use Illuminate\Filesystem\Filesystem;

class Helper
{
    /**
     * @var string
     */
    protected $module;

    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * @var Composer
     */
    protected $composer;

    protected $components = [
        'model'                      => '/Models/{{path}}/{{class}}.php',
        'attribute'                  => '/Models/{{path}}/Traits/Attribute/{{class}}Attribute.php',
        'relationship'               => '/Models/{{path}}/Traits/Relationship/{{class}}Relationship.php',
        'contract'                   => '/Contracts/{{class}}RepositoryContract.php',
        'repository'                 => '/Repositories/{{path}}/Eloquent{{class}}Repository.php',
        'service'                    => '/Services/{{path}}/{{class}}Service.php',
        'presenter'                  => '/Presenters/{{path}}/{{class}}Presenter.php',
        'controller'                 => '/Http/Controllers/{{path}}/{{class}}Controller.php',
        'store_request'              => '/Http/Requests/{{path}}/Store{{class}}Request.php',
        'update_request'             => '/Http/Requests/{{path}}/Update{{class}}Request.php',
        'delete_request'             => '/Http/Requests/{{path}}/Delete{{class}}Request.php',
        'permanently_delete_request' => '/Http/Requests/{{path}}/PermanentlyDelete{{class}}Request.php',
        'general_exception'        => '/Exceptions/GeneralException.php',
    ];

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
        $this->composer = app()['composer'];
    }

    /**
     * set the module.
     *
     * @return void
     */
    public function setModule($module)
    {
        $this->module = $module;
    }

    /**
     * get the components.
     *
     * @return void
     */
    public function getComponents()
    {
        return $this->components;
    }

    /**
     * make the module's files.
     *
     * @return boolean
     */
    public function make($component)
    {
        if ($this->files->exists($path = app_path($this->parse($this->components[$component])))) {
            return false;
        }

        $this->makeDirectory($path);

        $stub = $this->files->get(__DIR__ . '/../stubs/' . snake_case($component) . '.stub');

        $this->files->put($path, $this->parse($stub));

        return true;
    }

    /**
     * optimized bind and route for module.
     *
     * @return boolean
     */
    public function optimize()
    {
        return $this->bind() && $this->setRoute();
    }

    /**
     * set the resource routes.
     *
     * @return boolean
     */
    protected function setRoute()
    {
        if (file_put_contents(
                base_path('routes/web.php'),
                $this->parse(file_get_contents(__DIR__.'/../stubs/route.stub')),
                FILE_APPEND
            )) {
            return true;
        }
    }

    /**
     * bind the repository and repository's contract.
     *
     * @return boolean
     */
    protected function bind()
    {
        $path = app_path('/Providers/AppServiceProvider.php');
        $provider = $this->files->get($path);
        $newProvider = rtrim(substr($provider, 0, strrpos($provider, '}')), ' ');

        $binds = "    \$this->app->bind(\n            \App\Contracts\{{class}}RepositoryContract::class,\n            \App\Repositories\{{path}}\Eloquent{{class}}Repository::class\n        );\n    ";
        $replaces = [
            '{{path}}'     => $this->module,
            '{{class}}'    => ucfirst(class_basename($this->module))
        ];

        $binds = str_replace(array_keys($replaces), array_values($replaces), $binds);

        if (false === strpos($provider, $binds)) {
            $preg = '/register[.\s\S]*?\{([.\s\S]*?)\}$/';

            preg_match($preg, $newProvider, $match);
            $register = str_replace('{'. $match[1] .'}', '{'. $match[1] . $binds .'}', $match[0]);

            $provider = str_replace($match[0], $register, $provider);

            $this->files->put($path, $provider);

            return true;
        }
    }

    /**
     * parse the string for the stub, path and etc.
     *
     * @param  string $string
     * @return string
     */
    protected function parse($string)
    {
        $replaces = [
            '{{path}}'  => $this->module,
            '{{class}}' => ucfirst(class_basename($this->module)),
            '{{name}}'  => mb_strtolower(class_basename($this->module))
        ];

        return str_replace(array_keys($replaces), array_values($replaces), $string);
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

}