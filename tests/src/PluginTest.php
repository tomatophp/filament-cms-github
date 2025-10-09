<?php

use Filament\Facades\Filament;
use TomatoPHP\FilamentCmsGithub\FilamentCmsGithubPlugin;

it('registers plugin', function () {
    $panel = Filament::getCurrentOrDefaultPanel();

    $panel->plugins([
        FilamentCmsGithubPlugin::make(),
    ]);

    expect($panel->getPlugin('filament-cms-github'))
        ->not()
        ->toThrow(Exception::class);
});
