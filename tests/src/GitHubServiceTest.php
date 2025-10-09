<?php

namespace TomatoPHP\FilamentCmsGithub\Tests;

use Illuminate\Support\Facades\Http;
use TomatoPHP\FilamentCms\Models\Post;
use TomatoPHP\FilamentCmsGithub\Services\GitHubService;
use TomatoPHP\FilamentCmsGithub\Tests\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::factory()->create());
    $this->service = new GitHubService;
});

it('can extract repository name from GitHub URL', function () {
    $repo = $this->service->extractRepoFromUrl('https://github.com/tomatophp/filament-cms');
    expect($repo)->toBe('tomatophp/filament-cms');
});

it('can extract repository name from GitHub www URL', function () {
    $repo = $this->service->extractRepoFromUrl('https://www.github.com/tomatophp/filament-cms');
    expect($repo)->toBe('tomatophp/filament-cms');
});

it('can fetch GitHub repository data', function () {
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
    ]);

    $data = $this->service->fetchRepoData('tomatophp/filament-cms');

    expect($data)->toBeArray()
        ->and($data['id'])->toBe(123456)
        ->and($data['name'])->toBe('filament-cms')
        ->and($data['full_name'])->toBe('tomatophp/filament-cms');
});

it('returns null when GitHub API fails', function () {
    Http::fake([
        'https://api.github.com/repos/invalid/repo' => Http::response([], 404),
    ]);

    $data = $this->service->fetchRepoData('invalid/repo');

    expect($data)->toBeNull();
});

it('can fetch README content', function () {
    Http::fake([
        'https://raw.githubusercontent.com/tomatophp/filament-cms/master/README.md' => Http::response('# Filament CMS', 200),
    ]);

    $readme = $this->service->fetchReadme('tomatophp/filament-cms', 'master');

    expect($readme)->toBe('# Filament CMS');
});

it('returns null when README is not found', function () {
    Http::fake([
        'https://raw.githubusercontent.com/tomatophp/filament-cms/master/README.md' => Http::response([], 404),
    ]);

    $readme = $this->service->fetchReadme('tomatophp/filament-cms', 'master');

    expect($readme)->toBeNull();
});

it('can fetch Packagist data', function () {
    Http::fake([
        'https://packagist.org/packages/tomatophp/filament-cms.json' => Http::response([
            'package' => [
                'downloads' => [
                    'total' => 10000,
                    'monthly' => 500,
                    'daily' => 50,
                ],
                'versions' => [
                    '1.0.0' => [
                        'keywords' => ['filament', 'cms', 'laravel'],
                    ],
                ],
            ],
        ], 200),
    ]);

    $data = $this->service->fetchPackagistData('tomatophp/filament-cms');

    expect($data)->toBeArray()
        ->and($data['package']['downloads']['total'])->toBe(10000);
});

it('returns null when Packagist data is not found', function () {
    Http::fake([
        'https://packagist.org/packages/invalid/package.json' => Http::response(['status' => 'error'], 404),
    ]);

    $data = $this->service->fetchPackagistData('invalid/package');

    expect($data)->toBeNull();
});

it('can find or create post from GitHub data', function () {
    $github = [
        'full_name' => 'tomatophp/filament-cms',
        'name' => 'filament-cms',
    ];

    $post = $this->service->findOrCreatePost($github);

    expect($post)->toBeInstanceOf(Post::class)
        ->and($post->slug)->toBeNull(); // New post has no slug yet
});

it('can restore soft-deleted post', function () {
    $post = Post::factory()->create([
        'slug' => 'filament-cms',
        'deleted_at' => now(),
    ]);

    $github = [
        'full_name' => 'tomatophp/filament-cms',
        'name' => 'filament-cms',
    ];

    $restoredPost = $this->service->findOrCreatePost($github);

    expect($restoredPost->id)->toBe($post->id)
        ->and($restoredPost->deleted_at)->toBeNull();
});

it('can update post with GitHub data', function () {
    $user = auth()->user();
    $post = Post::factory()->create();

    $github = [
        'name' => 'filament-cms',
        'full_name' => 'tomatophp/filament-cms',
        'description' => 'Full CMS System',
        'default_branch' => 'master',
    ];

    $readme = '# Filament CMS';

    $packagist = [
        'package' => [
            'versions' => [
                '1.0.0' => [
                    'keywords' => ['filament', 'cms'],
                ],
            ],
        ],
    ];

    $updatedPost = $this->service->updatePostFromGitHub(
        $post,
        $github,
        $readme,
        $packagist,
        'https://github.com/tomatophp/filament-cms',
        $user->id,
        get_class($user)
    );

    expect($updatedPost->getTranslation('title', 'en'))->toBe('filament-cms')
        ->and($updatedPost->getTranslation('body', 'en'))->toBe('# Filament CMS')
        ->and($updatedPost->type)->toBe('open-source')
        ->and($updatedPost->is_published)->toBeTrue()
        ->and($updatedPost->author_id)->toBe($user->id);
});

it('can update post meta fields', function () {
    $post = Post::factory()->create();

    $github = [
        'stargazers_count' => 100,
        'watchers_count' => 50,
        'language' => 'PHP',
        'forks_count' => 25,
        'open_issues_count' => 5,
        'default_branch' => 'master',
        'homepage' => 'https://docs.tomatophp.com',
    ];

    $packagist = [
        'package' => [
            'downloads' => [
                'total' => 10000,
                'monthly' => 500,
                'daily' => 50,
            ],
        ],
    ];

    $this->service->updatePostMeta($post, $github, $packagist);

    $post->refresh();

    // Verify meta was saved (using meta() method to retrieve)
    expect($post->meta('github_starts'))->toBe(100)
        ->and($post->meta('downloads_total'))->toBe(10000);
});

it('can import repository successfully', function () {
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

    $user = auth()->user();
    $post = $this->service->importRepository(
        'https://github.com/tomatophp/filament-cms',
        $user->id,
        get_class($user)
    );

    expect($post)->toBeInstanceOf(Post::class)
        ->and($post->getTranslation('title', 'en'))->toBe('filament-cms')
        ->and($post->type)->toBe('open-source');
});

it('returns null when repository is not found', function () {
    Http::fake([
        'https://api.github.com/repos/invalid/repo' => Http::response([], 404),
    ]);

    $post = $this->service->importRepository('https://github.com/invalid/repo');

    expect($post)->toBeNull();
});

it('can refresh existing post', function () {
    $post = Post::factory()->create([
        'type' => 'open-source',
        'meta_url' => 'https://github.com/tomatophp/filament-cms',
        'title' => ['en' => 'Old Title', 'ar' => 'Old Title'],
    ]);

    Http::fake([
        'https://api.github.com/repos/tomatophp/filament-cms' => Http::response([
            'id' => 123456,
            'name' => 'New Name',
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
                'downloads' => [
                    'total' => 20000,
                    'monthly' => 1000,
                    'daily' => 100,
                ],
                'versions' => [
                    '2.0.0' => [
                        'keywords' => ['filament', 'cms', 'laravel'],
                    ],
                ],
            ],
        ], 200),
    ]);

    $result = $this->service->refreshPost($post);

    $post->refresh();

    expect($result)->toBeTrue()
        ->and($post->getTranslation('title', 'en'))->toBe('New Name')
        ->and($post->getTranslation('body', 'en'))->toBe('# Updated README');
});

it('returns false when refreshing post without meta_url', function () {
    $post = Post::factory()->create([
        'type' => 'open-source',
        'meta_url' => null,
    ]);

    $result = $this->service->refreshPost($post);

    expect($result)->toBeFalse();
});

it('can refresh all posts', function () {
    Post::query()->delete(); // Clear any existing posts

    Post::factory()->count(3)->create([
        'type' => 'open-source',
        'meta_url' => 'https://github.com/tomatophp/filament-cms',
        'slug' => fn () => 'filament-cms-' . uniqid(),
    ]);

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
        ], 200),
        'https://raw.githubusercontent.com/tomatophp/filament-cms/master/README.md' => Http::response('# Filament CMS', 200),
        'https://packagist.org/packages/tomatophp/filament-cms.json' => Http::response([
            'package' => [
                'downloads' => ['total' => 10000, 'monthly' => 500, 'daily' => 50],
                'versions' => ['1.0.0' => ['keywords' => ['filament']]],
            ],
        ], 200),
    ]);

    $count = $this->service->refreshAllPosts();

    expect($count)->toBe(3);
});
