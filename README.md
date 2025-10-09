![Screenshot](https://raw.githubusercontent.com/tomatophp/filament-cms-github/master/art/fadymondy-tomato-cms-github.jpg)

# Filament CMS GitHub Integration

[![Latest Stable Version](https://poser.pugx.org/tomatophp/filament-cms-github/version.svg)](https://packagist.org/packages/tomatophp/filament-cms-github)
[![License](https://poser.pugx.org/tomatophp/filament-cms-github/license.svg)](https://packagist.org/packages/tomatophp/filament-cms-github)
[![Downloads](https://poser.pugx.org/tomatophp/filament-cms-github/d/total.svg)](https://packagist.org/packages/tomatophp/filament-cms-github)

GitHub integration for TomatoPHP CMS that automatically imports and synchronizes GitHub repository documentation (README files) as posts in your CMS. Perfect for maintaining documentation sites, portfolio showcases, or open-source project listings.


## Screenshot

![Import Action](https://raw.githubusercontent.com/tomatophp/filament-cms-github/master/art/import.png)

## Features

- 🚀 **Automatic Import** - Import GitHub repositories with a single click
- 📄 **README Sync** - Automatically fetches and converts README.md to post content
- 📊 **GitHub Stats** - Imports stars, forks, watchers, and issue counts
- 📦 **Packagist Integration** - Fetches download statistics for PHP packages
- 🔄 **Bulk Refresh** - Update all imported repositories at once
- 🎨 **Avatar Images** - Automatically downloads repository owner avatars
- 🏷️ **Auto Tagging** - Extracts keywords from Packagist data
- ⚡ **Queue Support** - Background processing for better performance
- 🔔 **Notifications** - User notifications on import success/failure
- 🧪 **Full Test Coverage** - 39 comprehensive tests

## Installation

Install the package via composer:

```bash
composer require tomatophp/filament-cms-github
```

Run the installation command:

```bash
php artisan filament-cms-github:install
```

Register the plugin in your Filament panel provider (`/app/Providers/Filament/AdminPanelProvider.php`):

```php
use TomatoPHP\FilamentCmsGithub\FilamentCmsGithubPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(FilamentCmsGithubPlugin::make());
}
```

## Requirements

- PHP 8.2 or higher
- Laravel 10.x or 11.x
- Filament 3.x
- TomatoPHP CMS 4.x

## Configuration

### Queue Setup

This package dispatches jobs to the queue for better performance. Make sure your queue is configured:

```bash
# Run queue worker
php artisan queue:work
```

### Post Type Configuration

Imported repositories are saved as posts with type `open-source`. You can customize this in your CMS configuration.

## Usage

### Import Single Repository

1. Navigate to your CMS Posts page in Filament
2. Click the **"Github Import"** action button
3. Enter repository URL(s) and redirect path(s)
4. Click submit

**Example:**

```
URL: https://github.com/tomatophp/filament-cms
Redirect: /posts
```

### Import Multiple Repositories

You can import multiple repositories at once:

```
URL                                          | Redirect URL
-------------------------------------------- | ------------
https://github.com/tomatophp/filament-cms   | /cms
https://github.com/tomatophp/filament-alerts| /alerts
https://github.com/tomatophp/filament-users | /users
```

### Refresh All Repositories

To update all previously imported repositories with latest data:

1. Use the **"Refresh Github Links"** action
2. All posts with `type = 'open-source'` and a `meta_url` will be updated

### Programmatic Usage

#### Import Repository

```php
use TomatoPHP\FilamentCmsGithub\Services\GitHubService;

$service = app(GitHubService::class);

$post = $service->importRepository(
    url: 'https://github.com/tomatophp/filament-cms',
    userId: auth()->id(),
    userType: get_class(auth()->user())
);
```

#### Refresh Existing Post

```php
use TomatoPHP\FilamentCmsGithub\Services\GitHubService;
use TomatoPHP\FilamentCms\Models\Post;

$service = app(GitHubService::class);
$post = Post::find(1);

$success = $service->refreshPost($post);
```

#### Refresh All Posts

```php
use TomatoPHP\FilamentCmsGithub\Services\GitHubService;

$service = app(GitHubService::class);
$count = $service->refreshAllPosts(); // Returns number of posts refreshed
```

## API Reference

### GitHubService

The main service class for GitHub operations.

#### `extractRepoFromUrl(string $url): string`

Extracts repository name from GitHub URL.

```php
$repo = $service->extractRepoFromUrl('https://github.com/tomatophp/filament-cms');
// Returns: 'tomatophp/filament-cms'
```

#### `fetchRepoData(string $repo): ?array`

Fetches repository data from GitHub API.

```php
$data = $service->fetchRepoData('tomatophp/filament-cms');
// Returns array with: id, name, full_name, description, stargazers_count, etc.
```

#### `fetchReadme(string $repo, string $branch = 'main'): ?string`

Fetches README content from repository.

```php
$readme = $service->fetchReadme('tomatophp/filament-cms', 'master');
// Returns: markdown content as string
```

#### `fetchPackagistData(string $packageName): ?array`

Fetches package data from Packagist.

```php
$data = $service->fetchPackagistData('tomatophp/filament-cms');
// Returns array with downloads, versions, keywords, etc.
```

#### `importRepository(string $url, ?int $userId = null, ?string $userType = null): ?Post`

Imports a complete repository as a post.

```php
$post = $service->importRepository(
    url: 'https://github.com/tomatophp/filament-cms',
    userId: 1,
    userType: 'App\\Models\\User'
);
```

#### `refreshPost(Post $post): bool`

Refreshes an existing post with latest GitHub data.

```php
$success = $service->refreshPost($post);
```

#### `refreshAllPosts(): int`

Refreshes all posts of type 'open-source' with meta_url.

```php
$count = $service->refreshAllPosts();
```

### Filament Actions

#### GithubImportAction

Provides a Filament action for importing repositories.

```php
use TomatoPHP\FilamentCmsGithub\Filament\Actions\GithubImportAction;

// In your resource
protected function getHeaderActions(): array
{
    return [
        GithubImportAction::make(),
    ];
}
```

#### GithubRefreshAction

Provides a Filament action for refreshing all repositories.

```php
use TomatoPHP\FilamentCmsGithub\Filament\Actions\GithubRefreshAction;

protected function getHeaderActions(): array
{
    return [
        GithubRefreshAction::make(),
    ];
}
```

## Data Structure

### Post Fields

Imported repositories are saved with the following structure:

```php
[
    'title' => ['en' => 'Repository Name', 'ar' => 'Repository Name'],
    'slug' => 'repository-name',
    'body' => ['en' => '# README content...', 'ar' => '# README content...'],
    'short_description' => ['en' => 'Description', 'ar' => 'Description'],
    'keywords' => ['en' => 'keyword1,keyword2', 'ar' => 'keyword1,keyword2'],
    'type' => 'open-source',
    'meta_url' => 'https://github.com/owner/repo',
    'is_published' => true,
    'published_at' => now(),
]
```

### Meta Fields

GitHub statistics are stored as meta fields:

- `github_starts` - Star count
- `github_watchers` - Watcher count
- `github_language` - Primary language
- `github_forks` - Fork count
- `github_open_issues` - Open issue count
- `github_default_branch` - Default branch name
- `github_docs` - Homepage/documentation URL
- `downloads_total` - Total Packagist downloads (if available)
- `downloads_monthly` - Monthly downloads
- `downloads_daily` - Daily downloads

### Accessing Meta Fields

```php
$post = Post::find(1);

// Get star count
$stars = $post->meta('github_starts');

// Get total downloads
$downloads = $post->meta('downloads_total');
```

## Jobs

### GitHubMetaGetterJob

Handles importing a single repository in the background.

```php
use TomatoPHP\FilamentCmsGithub\Jobs\GitHubMetaGetterJob;

dispatch(new GitHubMetaGetterJob(
    url: 'https://github.com/tomatophp/filament-cms',
    redirect: '/posts',
    userId: auth()->id(),
    userType: get_class(auth()->user()),
    panel: 'admin'
));
```

### GitHubMetaRefreshJob

Handles refreshing all repositories in the background.

```php
use TomatoPHP\FilamentCmsGithub\Jobs\GitHubMetaRefreshJob;

dispatch(new GitHubMetaRefreshJob);
```

## Events

The package dispatches the following events:

- `TomatoPHP\FilamentCms\Events\PostCreated` - When a repository is imported
- `TomatoPHP\FilamentCms\Events\PostUpdated` - When a repository is refreshed

## Notifications

Users receive notifications for:

- **Import Success** - Repository imported successfully with link to view post
- **Import Failure** - Repository import failed with error details
- **Refresh Success** - All repositories refreshed successfully

## Testing

The package includes comprehensive test coverage:

```bash
# Run tests
composer test

# Run tests with coverage
composer test-coverage

# Run static analysis
composer analyse

# Format code
composer format
```

### Test Structure

- **GitHubServiceTest** (17 tests) - Tests all service methods
- **GitHubActionsTest** (13 tests) - Tests Filament actions
- **GitHubJobsTest** (7 tests) - Tests job execution
- **PluginTest** (1 test) - Tests plugin registration

## Publish Assets

### Config File

```bash
php artisan vendor:publish --tag="filament-cms-github-config"
```

### Views

```bash
php artisan vendor:publish --tag="filament-cms-github-views"
```

### Translations

```bash
php artisan vendor:publish --tag="filament-cms-github-lang"
```

### Migrations

```bash
php artisan vendor:publish --tag="filament-cms-github-migrations"
```

## Troubleshooting

### Queue Not Processing

Make sure your queue worker is running:

```bash
php artisan queue:work
```

For development, you can use sync driver in `.env`:

```env
QUEUE_CONNECTION=sync
```

### GitHub API Rate Limits

GitHub API has rate limits. For authenticated requests (recommended), generate a personal access token and configure it in your application.

### README Not Found

The package tries multiple branch names automatically:
- `main`
- `master`
- Default branch from GitHub API

If README is not found, the post will be created with empty body content.

## Advanced Usage

### Custom Post Processing

You can listen to the `PostCreated` event to perform custom processing:

```php
use TomatoPHP\FilamentCms\Events\PostCreated;
use Illuminate\Support\Facades\Event;

Event::listen(PostCreated::class, function ($event) {
    $post = $event->model;

    // Custom processing here
    if ($post->type === 'open-source') {
        // Do something with imported repository
    }
});
```

### Custom Notification Handling

```php
use Illuminate\Support\Facades\Notification;
use TomatoPHP\FilamentCmsGithub\Jobs\GitHubMetaGetterJob;

class CustomNotificationHandler
{
    public function handle(GitHubMetaGetterJob $job)
    {
        // Custom notification logic
    }
}
```

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

### Development Setup

```bash
# Clone the repository
git clone https://github.com/tomatophp/filament-cms-github.git

# Install dependencies
composer install

# Run tests
composer test
```

## Testing

if you like to run `PEST` testing just use this command

```bash
composer test
```

## Code Style

if you like to fix the code style, just use this command

```bash
composer format
```

## PHPStan

if you like to check the code by `PHPStan` just use this command

```bash
composer analyse
```

## Other Filament Packages

Check out our [Awesome TomatoPHP](https://github.com/tomatophp/awesome)
