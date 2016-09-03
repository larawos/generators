<?php 

namespace Larawos\Generators\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\AppNamespaceDetectorTrait;

class Helper
{
    use AppNamespaceDetectorTrait;

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
        'model'                      => '/Models/{{path}}/{{class}}',
        'attribute'                  => '/Models/{{path}}/Trait/Attribute/{{class}}Attribute',
        'relationship'               => '/Models/{{path}}/Trait/Relationship/{{class}}Relationship',
        'contract'                   => '/Contracts/{{path}}/{{class}}RepositoryContract',
        'repository'                 => '/Repositories/{{path}}/Eloquent{{class}}Repository',
        'service'                    => '/Services/{{path}}/{{class}}Service',
        'presenter'                  => '/Presenters/{{path}}/{{class}}Presenter',
        'controller'                 => '/Http/Controllers/{{path}}/{{class}}Controller',
        'store_request'              => '/Http/Requests/{{path}}/Store{{class}}Request',
        'update_request'             => '/Http/Requests/{{path}}/Update{{class}}Request',
        'delete_request'             => '/Http/Requests/{{path}}/Delete{{class}}Request',
        'permanently_delete_request' => '/Http/Requests/{{path}}/PermanentlyDelete{{class}}Request',
        'migration'                  => 'migration',
        'view'                       => 'view',
        'include'                    => 'includes',
    ];

    protected $views = [
        'views/index',
        'views/show',
        'views/create',
        'views/edit'
    ];

    protected $includes = [
        'views/includes/presenter'
    ];

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
        $this->composer = app()['composer'];
    }

    public function setModule($module)
    {
        $this->module = $module;
    }

    public function getComponents()
    {
        return $this->components;
    }

    public function make($component)
    {
        switch ($component) {
            case 'migration':
                $success = $this->makeMigration($component);
                break;
            case 'view':
                $success = $this->makeViews($component);
                break;
            case 'include':
                $success = $this->makeIncludes($component);
                break;
            default:
                $success = $this->_make($component);
                break;
        }

        return $success;
    }

    public function bind()
    {
        $path = app_path() . '/Providers/AppServiceProvider.php';
        $provider = $this->files->get($path);
        $newProvider = rtrim(substr($provider, 0, strrpos($provider, '}')), ' ');

        $binds = "    \$this->app->bind(\n            \App\Contracts\{{path}}\{{class}}RepositoryContract::class,\n            \App\Repositories\{{path}}\Eloquent{{class}}Repository::class\n        );\n    ";
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

    protected function parse($component)
    {
        $stub = $this->files->get(__DIR__ . '/../stubs/' . snake_case($component) . '.stub');

        $replaces = [
            '{{path}}'     => $this->module,
            '{{class}}'    => ucfirst(class_basename($this->module)),
            '{{variable}}' => mb_strtolower(class_basename($this->module))
        ];

        return str_replace(array_keys($replaces), array_values($replaces), $stub);
    }

    protected function makeIncludes($component)
    {
        $status = false;

        foreach ($this->includes as $include) {
            if (!$this->files->exists($path = str_replace('{{variable}}', class_basename($include), $this->getIncludePath()))) {

                $this->makeDirectory($path);

                $this->files->put($path, $this->parse($include));

                $status = true;
            }
        }

        return $status;
    }

    protected function makeViews($component)
    {
        $status = false;

        foreach ($this->views as $view) {
            if (!$this->files->exists($path = str_replace('{{variable}}', class_basename($view), $this->getViewPath()))) {

                $this->makeDirectory($path);

                $this->files->put($path, $this->parse($view));

                $status = true;
            }
        }

        return $status;
    }

    protected function makeMigration($component)
    {
        if ($this->files->exists($path = $this->getMigrationPath())) {
            return false;
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->parse($component));

        $this->composer->dumpAutoloads();

        return true;
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

    protected function getPath($component)
    {
        $name = str_replace($this->getAppNamespace(), '', $this->module);

        $path = $this->components[$component];

        $replaces = [
            '{{path}}'     => $this->module,
            '{{class}}'    => ucfirst(class_basename($this->module))
        ];

        return app_path() .
        str_replace('{{class}}', class_basename($name), 
                str_replace('{{path}}', str_replace('\\', '/', $name), $path)
            )
        . '.php';
    }

    /**
     * Get the path to where we should store the migration.
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        return base_path() . '/database/migrations/create_' . date('Y_m_d_His') . '_' . mb_strtolower(class_basename($this->module)) . '_table.php';
    }

    /**
     * Get the path to where we should store the view.
     *
     * @return string
     */
    protected function getViewPath()
    {
        return base_path() . '/resources/views/' . mb_strtolower(str_replace('\\', '/', $this->module)) . '/{{variable}}.blade.php';
    }

    /**
     * Get the path to where we should store the includes.
     *
     * @return string
     */
    protected function getIncludePath()
    {
        return base_path() . '/resources/views/includes/{{variable}}/' .
        mb_strtolower(class_basename($this->module)) . '.blade.php';
    }

    private function _make($component)
    {
        if ($this->files->exists($path = $this->getPath($component))) {
            return false;
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->parse($component));

        return true;
    }

}
