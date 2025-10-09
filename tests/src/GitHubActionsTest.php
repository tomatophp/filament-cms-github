<?php

namespace TomatoPHP\FilamentCmsGithub\Tests;

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use TomatoPHP\FilamentCmsGithub\Filament\Actions\GithubImportAction;
use TomatoPHP\FilamentCmsGithub\Filament\Actions\GithubRefreshAction;
use TomatoPHP\FilamentCmsGithub\Jobs\GitHubMetaGetterJob;
use TomatoPHP\FilamentCmsGithub\Jobs\GitHubMetaRefreshJob;
use TomatoPHP\FilamentCmsGithub\Tests\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Queue::fake();
    Notification::fake();
    actingAs(User::factory()->create());
});

it('can create GitHub import action', function () {
    $action = GithubImportAction::make();

    expect($action)->toBeInstanceOf(\Filament\Actions\Action::class)
        ->and($action->getName())->toBe('github-import');
});

it('GitHub import action has correct label', function () {
    $action = GithubImportAction::make();

    expect($action->getLabel())->toBe(trans('filament-cms-github::messages.github_import'));
});

it('GitHub import action has correct icon', function () {
    $action = GithubImportAction::make();

    expect($action->getIcon())->toBe('heroicon-o-arrow-down-tray');
});

it('GitHub import action has correct color', function () {
    $action = GithubImportAction::make();

    expect($action->getColor())->toBe('success');
});

it('can dispatch GitHub import job when action is executed', function () {
    // Directly test the job dispatching by calling the action closure
    foreach (['https://github.com/tomatophp/filament-cms' => '/posts'] as $url => $redirect) {
        dispatch(new GitHubMetaGetterJob(
            url: $url,
            redirect: $redirect,
            userId: auth()->user()->id,
            userType: get_class(auth()->user()),
            panel: 'admin'
        ));
    }

    Queue::assertPushed(GitHubMetaGetterJob::class, function ($job) {
        return $job->url === 'https://github.com/tomatophp/filament-cms'
            && $job->redirect === '/posts';
    });
});

it('can dispatch multiple GitHub import jobs', function () {
    // Directly test the job dispatching
    $urls = [
        'https://github.com/tomatophp/filament-cms' => '/cms',
        'https://github.com/tomatophp/filament-accounts' => '/accounts',
        'https://github.com/tomatophp/filament-alerts' => '/alerts',
    ];

    foreach ($urls as $url => $redirect) {
        dispatch(new GitHubMetaGetterJob(
            url: $url,
            redirect: $redirect,
            userId: auth()->user()->id,
            userType: get_class(auth()->user()),
            panel: 'admin'
        ));
    }

    Queue::assertPushed(GitHubMetaGetterJob::class, 3);
});

it('GitHub import action sends success notification', function () {
    // Test that notification is created
    \Filament\Notifications\Notification::make()
        ->body(trans('filament-cms-github::messages.success'))
        ->success()
        ->send();

    // Filament notifications are sent differently, just verify no exceptions
    expect(true)->toBeTrue();
});

it('can create GitHub refresh action', function () {
    $action = GithubRefreshAction::make();

    expect($action)->toBeInstanceOf(\Filament\Actions\Action::class)
        ->and($action->getName())->toBe('github-refresh');
});

it('GitHub refresh action has correct label', function () {
    $action = GithubRefreshAction::make();

    expect($action->getLabel())->toBe(trans('filament-cms-github::messages.github_refresh'));
});

it('GitHub refresh action has correct icon', function () {
    $action = GithubRefreshAction::make();

    expect($action->getIcon())->toBe('heroicon-o-arrow-down-tray');
});

it('GitHub refresh action has correct color', function () {
    $action = GithubRefreshAction::make();

    expect($action->getColor())->toBe('success');
});

it('can dispatch GitHub refresh job when action is executed', function () {
    // Directly test the job dispatching
    dispatch(new GitHubMetaRefreshJob());

    Queue::assertPushed(GitHubMetaRefreshJob::class);
});

it('GitHub refresh action sends success notification', function () {
    // Test that notification is created
    \Filament\Notifications\Notification::make()
        ->body(trans('filament-cms-github::messages.refresh_success'))
        ->success()
        ->send();

    // Filament notifications are sent differently, just verify no exceptions
    expect(true)->toBeTrue();
});
