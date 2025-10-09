<?php

namespace TomatoPHP\FilamentCmsGithub;

use Illuminate\Support\ServiceProvider;

class FilamentCmsGithubServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register generate command
        $this->commands([
            \TomatoPHP\FilamentCmsGithub\Console\FilamentCmsGithubInstall::class,
        ]);

        // Register Langs
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'filament-cms-github');

        // Publish Lang
        $this->publishes([
            __DIR__ . '/../resources/lang' => base_path('lang/vendor/filament-cms-github'),
        ], 'filament-cms-github-lang');

    }

    public function boot(): void
    {
        // you boot methods here
    }
}
