<?php

namespace TomatoPHP\FilamentCmsGithub\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use TomatoPHP\FilamentCms\Models\Post;

class GitHubService
{
    /**
     * Extract GitHub repository name from URL
     */
    public function extractRepoFromUrl(string $url): string
    {
        return Str::of($url)
            ->remove('https://github.com/', '')
            ->remove('https://www.github.com/', '')
            ->toString();
    }

    /**
     * Fetch GitHub repository data from API
     */
    public function fetchRepoData(string $repo): ?array
    {
        $response = Http::get('https://api.github.com/repos/' . $repo);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Fetch README content from GitHub repository
     */
    public function fetchReadme(string $repo, string $branch = 'main'): ?string
    {
        $response = Http::get('https://raw.githubusercontent.com/' . $repo . '/' . $branch . '/README.md');

        if ($response->successful()) {
            return $response->body();
        }

        return null;
    }

    /**
     * Fetch package data from Packagist
     */
    public function fetchPackagistData(string $packageName): ?array
    {
        $response = Http::get('https://packagist.org/packages/' . $packageName . '.json');

        if ($response->successful()) {
            $data = $response->json();
            if (! isset($data['status'])) {
                return $data;
            }
        }

        return null;
    }

    /**
     * Find or create post from GitHub data
     */
    public function findOrCreatePost(array $github): Post
    {
        $slug = str($github['full_name'])->explode('/')->last();
        $post = Post::query()->withTrashed()->where('slug', $slug)->first();

        if ($post) {
            if ($post->deleted_at) {
                $post->restore();
            }
            $post->clearMediaCollection('feature_image');
        } else {
            $post = new Post;
        }

        return $post;
    }

    /**
     * Update post with GitHub data
     */
    public function updatePostFromGitHub(Post $post, array $github, string $readme, ?array $packagist = null, ?string $url = null, ?int $userId = null, ?string $userType = null): Post
    {
        $post->title = [
            'ar' => $github['name'],
            'en' => $github['name'],
        ];
        $post->body = [
            'ar' => $readme,
            'en' => $readme,
        ];
        $post->short_description = [
            'ar' => $github['description'] ?? '',
            'en' => $github['description'] ?? '',
        ];

        // Only set slug if it's not already set (preserve existing slugs on refresh)
        if (empty($post->slug)) {
            $post->slug = str($github['full_name'])->explode('/')->last();
        }

        $post->meta = $github;

        if ($url) {
            $post->meta_url = $url;
        }

        $post->type = 'open-source';
        $post->is_published = true;
        $post->published_at = now();

        if ($userId && $userType) {
            $post->author_type = $userType;
            $post->author_id = $userId;
        }

        if ($packagist) {
            $keywords = collect($packagist['package']['versions'])->first()['keywords'] ?? [];
            $post->keywords = [
                'ar' => implode(',', $keywords),
                'en' => implode(',', $keywords),
            ];
        }

        $post->save();

        return $post;
    }

    /**
     * Update post meta fields with GitHub data
     */
    public function updatePostMeta(Post $post, array $github, ?array $packagist = null): void
    {
        if ($packagist) {
            $post->meta('downloads_total', $packagist['package']['downloads']['total'] ?? 0);
            $post->meta('downloads_monthly', $packagist['package']['downloads']['monthly'] ?? 0);
            $post->meta('downloads_daily', $packagist['package']['downloads']['daily'] ?? 0);
        }

        $post->meta('github_starts', $github['stargazers_count'] ?? 0);
        $post->meta('github_watchers', $github['watchers_count'] ?? 0);
        $post->meta('github_language', $github['language'] ?? '');
        $post->meta('github_forks', $github['forks_count'] ?? 0);
        $post->meta('github_open_issues', $github['open_issues_count'] ?? 0);
        $post->meta('github_default_branch', $github['default_branch'] ?? 'main');
        $post->meta('github_docs', $github['homepage'] ?? '');
    }

    /**
     * Add avatar image to post
     */
    public function addAvatarImage(Post $post, string $avatarUrl): void
    {
        try {
            $post->addMediaFromUrl($avatarUrl)->toMediaCollection('feature_image');
        } catch (\Exception $e) {
            \Log::error('Failed to add avatar image: ' . $e->getMessage());
        }
    }

    /**
     * Import a GitHub repository as a post
     */
    public function importRepository(string $url, ?int $userId = null, ?string $userType = null): ?Post
    {
        $repo = $this->extractRepoFromUrl($url);
        $github = $this->fetchRepoData($repo);

        if (! $github || ! isset($github['id'])) {
            return null;
        }

        $readme = $this->fetchReadme($repo, $github['default_branch'] ?? 'main');
        if (! $readme) {
            return null;
        }

        $packagist = $this->fetchPackagistData($github['full_name']);

        $post = $this->findOrCreatePost($github);
        $post = $this->updatePostFromGitHub($post, $github, $readme, $packagist, $url, $userId, $userType);
        $this->updatePostMeta($post, $github, $packagist);

        if (isset($github['owner']['avatar_url'])) {
            $this->addAvatarImage($post, $github['owner']['avatar_url']);
        }

        return $post;
    }

    /**
     * Refresh GitHub data for existing post
     */
    public function refreshPost(Post $post): bool
    {
        if (! $post->meta_url) {
            return false;
        }

        $repo = $this->extractRepoFromUrl($post->meta_url);
        $github = $this->fetchRepoData($repo);

        if (! $github || ! isset($github['id'])) {
            return false;
        }

        $readme = $this->fetchReadme($repo, $github['default_branch'] ?? 'main');
        if (! $readme) {
            return false;
        }

        $packagist = $this->fetchPackagistData($github['full_name']);

        $this->updatePostFromGitHub($post, $github, $readme, $packagist);
        $this->updatePostMeta($post, $github, $packagist);

        return true;
    }

    /**
     * Refresh all open-source posts
     */
    public function refreshAllPosts(): int
    {
        $posts = Post::query()
            ->where('type', 'open-source')
            ->whereNotNull('meta_url')
            ->get();

        $refreshedCount = 0;
        foreach ($posts as $post) {
            if ($this->refreshPost($post)) {
                $refreshedCount++;
            }
        }

        return $refreshedCount;
    }
}
