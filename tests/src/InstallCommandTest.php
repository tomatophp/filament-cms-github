<?php

use Illuminate\Support\Facades\Artisan;
use TomatoPHP\FilamentCmsGithub\Console\FilamentCmsGithubInstall;

use function Pest\Laravel\artisan;

it('registers the install command', function () {
    expect(Artisan::all())->toHaveKey('filament-cms-github:install')
        ->and(Artisan::all()['filament-cms-github:install'])->toBeInstanceOf(FilamentCmsGithubInstall::class);
});

it('runs the install command', function () {
    artisan('filament-cms-github:install')
        ->expectsOutputToContain('Filament CMS Github installed successfully.')
        ->assertSuccessful();
});
