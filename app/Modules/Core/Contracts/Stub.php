<?php

namespace App\Modules\Core\Contracts;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait Stub
{

    protected $directories;

    protected $disk;

    protected $module;

    protected $modulePath;

    protected $mainModulePath;


    protected function isModule($module)
    {
        return in_array($module, $this->directories);
    }

    protected function build()
    {
        $this->buildModuleDirectories();
        $this->view();
        $this->model();
        $this->config();
        $this->routes();
        $this->controller();
        $this->request();
        $this->observer();
        $this->command();
        $this->event();
        $this->jobs();
        $this->migrate();
        $this->serviceProvider();
    }

    protected function buildModuleDirectories()
    {
        $module  = $this->module;

        if ($this->isModule($module)) {
            $this->info('Module is already exists.');
            exit();
        }

        $source = $module . '/';
        $this->disk->makeDirectory($source);
        $this->disk->makeDirectory($source . 'Models');
        $this->disk->makeDirectory($source . 'Routes');
        $this->disk->makeDirectory($source . 'Commands');
        $this->disk->makeDirectory($source . 'Jobs');
        $this->disk->makeDirectory($source . 'Events');
        $this->disk->makeDirectory($source . 'Config');
        $this->disk->makeDirectory($source . 'Observers');
        $this->disk->makeDirectory($source . 'Views');
        $this->disk->makeDirectory($source . 'Http');
        $this->disk->makeDirectory($source . 'Http/Controllers');
        $this->disk->makeDirectory($source . 'Http/Requests');
        $this->disk->makeDirectory($source . 'Database/Migrations');
        $this->disk->makeDirectory($source . 'Database/Seeds');

        $this->info("{$this->module} module directory created successfuly.");
    }

    protected function getStub($type)
    {
        $vendorPath = '';
        if (!file_exists('core')) {
            $vendorPath = 'app/modules/';
        }

        return File::get(base_path($vendorPath . "core/stubs/$type.stub"));
    }

    protected function model()
    {
        $modelTemplate = str_replace(
            [
                '{{moduleName}}',
                '{{moduleNamePluralLowerCase}}',
            ],
            [
                $this->module,
                strtolower(Str::plural($this->module)),
            ],
            $this->getStub('Model')
        );

        $this->createStubToFile("Models/{$this->module}.php", $modelTemplate);
    }

    protected function config()
    {
        $moduleTemplate = str_replace(
            ['{{moduleName}}'],
            [$this->module],
            $this->getStub('Config')
        );

        $this->createStubToFile("Config/" . strtolower($this->module) . ".php", $moduleTemplate);
    }


    protected function routes()
    {
        $routeTemplate = str_replace(
            ['{{moduleNameSingularLowerCase}}', '{{moduleName}}'],
            [strtolower(Str::kebab($this->module)), $this->module],
            $this->getStub('Route')
        );

        $this->createStubToFile("Routes/web.php", $routeTemplate);
    }

    protected function controller()
    {
        $controllerTemplate = str_replace(
            [
                '{{moduleName}}',
                '{{moduleNamePluralLowerCase}}',
                '{{moduleNameSingularLowerCase}}'
            ],
            [
                $this->module,
                strtolower(Str::plural($this->module)),
                strtolower($this->module)
            ],
            $this->getStub('Controller')
        );

        $this->createStubToFile("Http/Controllers/{$this->module}Controller.php", $controllerTemplate);
    }

    protected function request()
    {
        $requestTemplate = str_replace(
            ['{{moduleName}}'],
            [$this->module],
            $this->getStub('Request')
        );

        $this->createStubToFile("Http/Requests/{$this->module}Request.php", $requestTemplate);
    }

    protected function observer()
    {
        $observerTemplate = str_replace(
            [
                '{{moduleName}}',
                '{{moduleNameSingularLowerCase}}'
            ],
            [
                $this->module,
                strtolower($this->module)
            ],
            $this->getStub('Observer')
        );

        $this->createStubToFile("Observers/{$this->module}Observer.php", $observerTemplate);
    }

    protected function serviceProvider()
    {
        $serviceProviderTemplate = str_replace(
            [
                '{{moduleName}}',
                '{{moduleNamePluralLowerCase}}',
                '{{moduleNameSingularLowerCase}}'
            ],
            [
                $this->module,
                strtolower(Str::plural($this->module)),
                strtolower($this->module)
            ],
            $this->getStub('ServiceProvider')
        );

        $this->createStubToFile("{$this->module}ServiceProvider.php", $serviceProviderTemplate);
    }

    protected function seed()
    {
        $this->info('Creating seeder file.');
        $seederTemplate = str_replace(
            [
                '{{moduleName}}'
            ],
            [
                $this->module
            ],
            $this->getStub('Seed')
        );

        $this->createStubToFile("Database/Seeds/{$this->module}TableSeeder.php", $seederTemplate);
    }

    protected function migrate()
    {

        $module = strtolower($this->module);
        $migrationPath = 'app/Modules/' . $module . '/Database/Migrations';
        $modulePlural = Str::plural($module);

        $this->info('Creating a migration scripts.');
        Artisan::call("make:migration create_{$module}_table --path={$migrationPath} --create={$modulePlural}");

        $this->seed();

        $this->info('Migration script created.');
    }

    protected function event()
    {
        $observerEvents = ['Creating', 'Created', 'Saving', 'Saved', 'Updating', 'Updated'];

        foreach ($observerEvents as $events) {
            $eventTemplate = str_replace(
                [
                    '{{className}}',
                    '{{moduleName}}',
                ],
                [
                    $this->module . $events,
                    $this->module,
                ],
                $this->getStub('Event')
            );
            $this->createStubToFile("Events/{$this->module}{$events}.php", $eventTemplate);
        }
    }

    protected function eventServiceProvider()
    {
        $hasEventServiceProvider = $this->anticipate('Do you wish to create event service provider (Yes or No)?', ['Yes', 'No']);

        if (strtolower($hasEventServiceProvider) === 'yes') {

            $eventServiceProviderTemplate = str_replace(
                [
                    '{{moduleName}}',
                ],
                [
                    $this->module,
                ],
                $this->getStub('EventServiceProvider')
            );

            $this->createStubToFile("{$this->module}EventServiceProvider.php", $eventServiceProviderTemplate);

            $this->eventSubscriber();
        }
    }

    protected function eventSubscriber()
    {
        $eventSubscriberTemplate = str_replace(
            [
                '{{moduleName}}',
            ],
            [
                $this->module,
            ],
            $this->getStub('EventSubscriber')
        );

        $this->createStubToFile("Listeners/{$this->module}EventSubscriber.php", $eventSubscriberTemplate);
    }

    protected function command()
    {
        $commandTemplate = str_replace(
            [
                '{{moduleName}}',
                '{{moduleNameSingularLowerCase}}'
            ],
            [
                $this->module,
                strtolower($this->module),
            ],
            $this->getStub('Command')
        );

        $this->createStubToFile("Commands/{$this->module}Command.php", $commandTemplate);
    }

    protected function jobs()
    {

        $jobTemplate = str_replace(
            [
                '{{moduleName}}',
                '{{moduleNameSingularLowerCase}}'
            ],
            [
                $this->module,
                strtolower($this->module),
            ],
            $this->getStub('Job')
        );

        $this->createStubToFile("Jobs/{$this->module}Job.php", $jobTemplate);
    }

    protected function view()
    {

        $viewTemplate = 'Welcome to ' . $this->module;

        $this->createStubToFile("Views/index.blade.php", $viewTemplate);
    }

    protected function createStubToFile($file, $template, $mainDirectory = false)
    {
        $path =  $this->module . '//';

        $this->disk->put(
            $path . $file,
            $template
        );

        $this->info("$file created successfuly.");
    }

    protected function createToPath($file, $template)
    {
        $this->disk->put(
            $file,
            $template
        );

        $this->info("{$this->disk->getAdapter()->getPathPrefix()}{$file} created successfuly");
    }
}
