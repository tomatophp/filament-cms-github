<?php

namespace TomatoPHP\FilamentCmsGithub;

use Illuminate\Support\ServiceProvider;


class FilamentCmsGithubServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //Register Config file
        $this->mergeConfigFrom(__DIR__.'/../config/filament-cms-github.php', 'filament-cms-github');

        //Publish Config
        $this->publishes([
           __DIR__.'/../config/filament-cms-github.php' => config_path('filament-cms-github.php'),
        ], 'filament-cms-github-config');

        //Register Langs
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'filament-cms-github');

        //Publish Lang
        $this->publishes([
           __DIR__.'/../resources/lang' => base_path('lang/vendor/filament-cms-github'),
        ], 'filament-cms-github-lang');
    }

    public function boot(): void
    {
        //you boot methods here
    }
}
