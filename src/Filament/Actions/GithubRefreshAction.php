<?php

namespace TomatoPHP\FilamentCmsGithub\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use TomatoPHP\FilamentCmsGithub\Jobs\GitHubMetaRefreshJob;

class GithubRefreshAction
{
    public static function make(): Action
    {
        return Action::make('github-refresh')
            ->label(trans('filament-cms-github::messages.github_refresh'))
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->action(function (array $data) {
                dispatch(new GitHubMetaRefreshJob);

                Notification::make()
                    ->body(trans('filament-cms-github::messages.refresh_success'))
                    ->success()
                    ->send();
            });
    }
}
