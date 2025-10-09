<?php

namespace TomatoPHP\FilamentCmsGithub\Jobs;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Event;
use TomatoPHP\FilamentCms\Events\PostCreated;
use TomatoPHP\FilamentCms\Filament\Resources\PostResource;
use TomatoPHP\FilamentCmsGithub\Services\GitHubService;

class GitHubMetaGetterJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $url,
        public ?string $redirect = null,
        public ?int $userId = null,
        public ?string $userType = null,
        public ?string $panel = null,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(GitHubService $service): void
    {
        try {
            $user = $this->userType::find($this->userId);
            $repo = $service->extractRepoFromUrl($this->url);

            $post = $service->importRepository($this->url, $this->userId, $this->userType);

            if ($post) {
                Event::dispatch(new PostCreated($post->toArray()));

                Notification::make()
                    ->title(trans('filament-cms-github::messages.importـnotifications.title'))
                    ->body(trans('filament-cms-github::messages.importـnotifications.description', ['name' => $post->meta['full_name'] ?? $repo]))
                    ->success()
                    ->actions([
                        Action::make('view')
                            ->label(trans('filament-cms-github::messages.importـnotifications.view'))
                            ->url(PostResource::getUrl('edit', ['record' => $post->id]))
                            ->icon('heroicon-o-eye'),
                    ])
                    ->sendToDatabase($user);
            } else {
                Notification::make()
                    ->title(trans('filament-cms-github::messages.importـnotifications.failed_title'))
                    ->body(trans('filament-cms-github::messages.importـnotifications.failed_description', ['name' => $repo]))
                    ->danger()
                    ->sendToDatabase($user);
            }
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }
    }
}
