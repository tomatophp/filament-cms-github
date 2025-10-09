<?php

namespace TomatoPHP\FilamentCmsGithub\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Notifications\Notification;
use TomatoPHP\FilamentCmsGithub\Jobs\GitHubMetaGetterJob;

class GithubImportAction
{
    public static function make(): Action
    {
        return Action::make('github-import')
            ->label(trans('filament-cms-github::messages.github_import'))
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->schema([
                KeyValue::make('urls')
                    ->required()
                    ->keyLabel(trans('filament-cms-github::messages.url'))
                    ->valueLabel(trans('filament-cms-github::messages.redirect_url')),
            ])
            ->action(function (array $data) {
                foreach ($data['urls'] as $url => $redirect) {
                    dispatch(new GitHubMetaGetterJob(
                        url: $url,
                        redirect: $redirect,
                        userId: auth()->user()->id,
                        userType: get_class(auth()->user()),
                        panel: filament()->getCurrentOrDefaultPanel()->getId()
                    ));
                }

                Notification::make()
                    ->body(trans('filament-cms-github::messages.success'))
                    ->success()
                    ->send();
            });
    }
}
