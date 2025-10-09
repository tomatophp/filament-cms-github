<?php

namespace TomatoPHP\FilamentCmsGithub;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentCmsGithubPlugin implements Plugin
{

    public function getId(): string
    {
        return 'filament-cms-github';
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): self
    {
        return new FilamentCmsGithubPlugin;
    }
}
