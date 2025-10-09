<?php

use Filament\Facades\Filament;
use TomatoPHP\FilamentCmsYoutube\FilamentCmsYoutubePlugin;

it('registers plugin', function () {
    $panel = Filament::getCurrentOrDefaultPanel();

    $panel->plugins([
        FilamentCmsYoutubePlugin::make(),
    ]);

    expect($panel->getPlugin('filament-youtube-cms'))
        ->not()
        ->toThrow(Exception::class);
});
