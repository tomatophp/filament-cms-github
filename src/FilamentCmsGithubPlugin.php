<?php

namespace TomatoPHP\FilamentCmsGithub;

use Filament\Contracts\Plugin;
use Filament\Panel;
use TomatoPHP\FilamentCms\Facades\FilamentCMS;
use TomatoPHP\FilamentCmsGithub\Filament\Actions\GithubImportAction;

class FilamentCmsGithubPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-cms-github';
    }

    public function register(Panel $panel): void
    {
        FilamentCMS::registerImportAction(GithubImportAction::make());
    }

    public function boot(Panel $panel): void {}

    public static function make(): self
    {
        return new FilamentCmsGithubPlugin;
    }
}
