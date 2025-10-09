<?php

namespace TomatoPHP\FilamentCmsGithub\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Event;
use TomatoPHP\FilamentCms\Events\PostUpdated;
use TomatoPHP\FilamentCms\Models\Post;
use TomatoPHP\FilamentCmsGithub\Services\GitHubService;

class GitHubMetaRefreshJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(GitHubService $service): void
    {
        $posts = Post::query()
            ->where('type', 'open-source')
            ->whereNotNull('meta_url')
            ->get();

        foreach ($posts as $post) {
            if ($service->refreshPost($post)) {
                Event::dispatch(new PostUpdated($post->toArray()));
            }
        }
    }
}
