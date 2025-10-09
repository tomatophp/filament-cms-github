<?php

namespace TomatoPHP\FilamentCmsGithub;

use Illuminate\Support\ServiceProvider;


class FilamentCmsGithubServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //Register generate command
        $this->commands([
           \TomatoPHP\FilamentCmsGithub\Console\FilamentCmsGithubInstall::class,
        ]);
 
        //Register Config file
        $this->mergeConfigFrom(__DIR__.'/../config/filament-cms-github.php', 'filament-cms-github');
 
        //Publish Config
        $this->publishes([
           __DIR__.'/../config/filament-cms-github.php' => config_path('filament-cms-github.php'),
        ], 'filament-cms-github-config');
 
        //Register Migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
 
        //Publish Migrations
        $this->publishes([
           __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'filament-cms-github-migrations');
        //Register views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-cms-github');
 
        //Publish Views
        $this->publishes([
           __DIR__.'/../resources/views' => resource_path('views/vendor/filament-cms-github'),
        ], 'filament-cms-github-views');
 
        //Register Langs
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'filament-cms-github');
 
        //Publish Lang
        $this->publishes([
           __DIR__.'/../resources/lang' => base_path('lang/vendor/filament-cms-github'),
        ], 'filament-cms-github-lang');
 
        //Register Routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
 
    }

    public function boot(): void
    {
        //you boot methods here
    }
}
