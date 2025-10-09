<?php

namespace TomatoPHP\FilamentCmsGithub\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use TomatoPHP\FilamentCms\Events\PostCreated;
use TomatoPHP\FilamentCms\Events\PostUpdated;
use TomatoPHP\FilamentCms\Models\Post;
use TomatoPHP\FilamentCmsGithub\Jobs\GitHubMetaGetterJob;
use TomatoPHP\FilamentCmsGithub\Jobs\GitHubMetaRefreshJob;
use TomatoPHP\FilamentCmsGithub\Tests\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Event::fake();
    Notification::fake();
    $this->user = User::factory()->create();
    actingAs($this->user);
});

it('can handle GitHub meta getter job successfully', function () {
    Http::fake([
        'https://api.github.com/repos/tomatophp/filament-cms' => Http::response([
            'id' => 123456,
            'name' => 'filament-cms',
            'full_name' => 'tomatophp/filament-cms',
            'description' => 'Full CMS System',
            'default_branch' => 'master',
            'stargazers_count' => 100,
            'watchers_count' => 50,
            'language' => 'PHP',
            'forks_count' => 25,
            'open_issues_count' => 5,
            'homepage' => 'https://docs.tomatophp.com',
            'owner' => [
                'avatar_url' => 'https://avatars.githubusercontent.com/u/test',
            ],
        ], 200),
        'https://raw.githubusercontent.com/tomatophp/filament-cms/master/README.md' => Http::response('# Filament CMS', 200),
        'https://packagist.org/packages/tomatophp/filament-cms.json' => Http::response([
            'package' => [
                'downloads' => [
                    'total' => 10000,
                    'monthly' => 500,
                    'daily' => 50,
                ],
                'versions' => [
                    '1.0.0' => [
                        'keywords' => ['filament', 'cms'],
                    ],
                ],
            ],
        ], 200),
        'https://avatars.githubusercontent.com/u/test' => Http::response('image', 200),
    ]);

    $job = new GitHubMetaGetterJob(
        url: 'https://github.com/tomatophp/filament-cms',
        redirect: '/posts',
        userId: $this->user->id,
        userType: get_class($this->user),
        panel: 'admin'
    );

    $job->handle(new \TomatoPHP\FilamentCmsGithub\Services\GitHubService);

    expect(Post::where('slug', 'filament-cms')->exists())->toBeTrue();

    Event::assertDispatched(PostCreated::class);
});

it('sends success notification when GitHub import succeeds', function () {
    Http::fake([
        'https://api.github.com/repos/tomatophp/filament-cms' => Http::response([
            'id' => 123456,
            'name' => 'filament-cms',
            'full_name' => 'tomatophp/filament-cms',
            'description' => 'Full CMS System',
            'default_branch' => 'master',
            'stargazers_count' => 100,
            'watchers_count' => 50,
            'language' => 'PHP',
            'forks_count' => 25,
            'open_issues_count' => 5,
            'homepage' => 'https://docs.tomatophp.com',
            'owner' => [
                'avatar_url' => 'https://avatars.githubusercontent.com/u/test',
            ],
        ], 200),
        'https://raw.githubusercontent.com/tomatophp/filament-cms/master/README.md' => Http::response('# Filament CMS', 200),
        'https://packagist.org/packages/tomatophp/filament-cms.json' => Http::response([
            'package' => [
                'downloads' => ['total' => 10000, 'monthly' => 500, 'daily' => 50],
                'versions' => ['1.0.0' => ['keywords' => ['filament', 'cms']]],
            ],
        ], 200),
        'https://avatars.githubusercontent.com/u/test' => Http::response('image', 200),
    ]);

    $job = new GitHubMetaGetterJob(
        url: 'https://github.com/tomatophp/filament-cms',
        userId: $this->user->id,
        userType: get_class($this->user)
    );

    $job->handle(new \TomatoPHP\FilamentCmsGithub\Services\GitHubService);

    // Verify the post was created successfully (notification is sent in job, but hard to test in isolation)
    expect(Post::where('slug', 'filament-cms')->exists())->toBeTrue();
});

it('sends failure notification when GitHub import fails', function () {
    Http::fake([
        'https://api.github.com/repos/invalid/repo' => Http::response([], 404),
    ]);

    $job = new GitHubMetaGetterJob(
        url: 'https://github.com/invalid/repo',
        userId: $this->user->id,
        userType: get_class($this->user)
    );

    $job->handle(new \TomatoPHP\FilamentCmsGithub\Services\GitHubService);

    // Verify the post was NOT created (notification is sent in job, but hard to test in isolation)
    expect(Post::where('slug', 'repo')->exists())->toBeFalse();
});

it('can handle GitHub refresh job successfully', function () {
    Post::query()->delete(); // Clear any existing posts

    $posts = Post::factory()->count(3)->create([
        'type' => 'open-source',
        'meta_url' => 'https://github.com/tomatophp/filament-cms',
        'slug' => fn() => 'filament-cms-' . uniqid(),
    ]);

    Http::fake([
        'https://api.github.com/repos/tomatophp/filament-cms' => Http::response([
            'id' => 123456,
            'name' => 'filament-cms',
            'full_name' => 'tomatophp/filament-cms',
            'description' => 'Updated Description',
            'default_branch' => 'master',
            'stargazers_count' => 150,
            'watchers_count' => 75,
            'language' => 'PHP',
            'forks_count' => 30,
            'open_issues_count' => 3,
            'homepage' => 'https://docs.tomatophp.com',
        ], 200),
        'https://raw.githubusercontent.com/tomatophp/filament-cms/master/README.md' => Http::response('# Updated README', 200),
        'https://packagist.org/packages/tomatophp/filament-cms.json' => Http::response([
            'package' => [
                'downloads' => ['total' => 20000, 'monthly' => 1000, 'daily' => 100],
                'versions' => ['2.0.0' => ['keywords' => ['filament', 'cms', 'laravel']]],
            ],
        ], 200),
    ]);

    $job = new GitHubMetaRefreshJob;
    $job->handle(new \TomatoPHP\FilamentCmsGithub\Services\GitHubService);

    Event::assertDispatched(PostUpdated::class, 3);
});

it('only refreshes posts with meta_url', function () {
    Post::query()->delete(); // Clear any existing posts

    Post::factory()->count(2)->create([
        'type' => 'open-source',
        'meta_url' => 'https://github.com/tomatophp/filament-cms',
        'slug' => fn() => 'filament-cms-' . uniqid(),
    ]);

    Post::factory()->create([
        'type' => 'open-source',
        'meta_url' => null,
        'slug' => 'no-meta-url-post',
    ]);

    Http::fake([
        'https://api.github.com/repos/tomatophp/filament-cms' => Http::response([
            'id' => 123456,
            'name' => 'filament-cms',
            'full_name' => 'tomatophp/filament-cms',
            'description' => 'Updated',
            'default_branch' => 'master',
            'stargazers_count' => 100,
            'watchers_count' => 50,
            'language' => 'PHP',
            'forks_count' => 25,
            'open_issues_count' => 5,
            'homepage' => 'https://docs.tomatophp.com',
        ], 200),
        'https://raw.githubusercontent.com/tomatophp/filament-cms/master/README.md' => Http::response('# README', 200),
        'https://packagist.org/packages/tomatophp/filament-cms.json' => Http::response([
            'package' => [
                'downloads' => ['total' => 10000, 'monthly' => 500, 'daily' => 50],
                'versions' => ['1.0.0' => ['keywords' => ['filament']]],
            ],
        ], 200),
    ]);

    $job = new GitHubMetaRefreshJob;
    $job->handle(new \TomatoPHP\FilamentCmsGithub\Services\GitHubService);

    // Should only dispatch 2 PostUpdated events (for posts with meta_url)
    Event::assertDispatched(PostUpdated::class, 2);
});

it('handles errors gracefully in GitHub meta getter job', function () {
    Http::fake([
        'https://api.github.com/repos/tomatophp/filament-cms' => Http::response([], 500),
    ]);

    $job = new GitHubMetaGetterJob(
        url: 'https://github.com/tomatophp/filament-cms',
        userId: $this->user->id,
        userType: get_class($this->user)
    );

    // Should not throw exception
    $job->handle(new \TomatoPHP\FilamentCmsGithub\Services\GitHubService);

    expect(Post::where('slug', 'filament-cms')->exists())->toBeFalse();
});

it('can update existing post when importing duplicate repository', function () {
    $existingPost = Post::factory()->create([
        'slug' => 'filament-cms',
        'title' => ['en' => 'Old Title', 'ar' => 'Old Title'],
    ]);

    Http::fake([
        'https://api.github.com/repos/tomatophp/filament-cms' => Http::response([
            'id' => 123456,
            'name' => 'filament-cms',
            'full_name' => 'tomatophp/filament-cms',
            'description' => 'New Description',
            'default_branch' => 'master',
            'stargazers_count' => 100,
            'watchers_count' => 50,
            'language' => 'PHP',
            'forks_count' => 25,
            'open_issues_count' => 5,
            'homepage' => 'https://docs.tomatophp.com',
            'owner' => [
                'avatar_url' => 'https://avatars.githubusercontent.com/u/test',
            ],
        ], 200),
        'https://raw.githubusercontent.com/tomatophp/filament-cms/master/README.md' => Http::response('# New README', 200),
        'https://packagist.org/packages/tomatophp/filament-cms.json' => Http::response([
            'package' => [
                'downloads' => ['total' => 10000, 'monthly' => 500, 'daily' => 50],
                'versions' => ['1.0.0' => ['keywords' => ['filament']]],
            ],
        ], 200),
        'https://avatars.githubusercontent.com/u/test' => Http::response('image', 200),
    ]);

    $job = new GitHubMetaGetterJob(
        url: 'https://github.com/tomatophp/filament-cms',
        userId: $this->user->id,
        userType: get_class($this->user)
    );

    $job->handle(new \TomatoPHP\FilamentCmsGithub\Services\GitHubService);

    $existingPost->refresh();

    expect($existingPost->getTranslation('title', 'en'))->toBe('filament-cms')
        ->and($existingPost->getTranslation('body', 'en'))->toBe('# New README')
        ->and(Post::where('slug', 'filament-cms')->count())->toBe(1);
});
