<?php

namespace TomatoPHP\FilamentCmsYoutube\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Config\Repository;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\Attributes\WithEnv;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as BaseTestCase;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use TomatoPHP\FilamentCmsYoutube\FilamentCmsYoutubeServiceProvider;
use TomatoPHP\FilamentCmsYoutube\Tests\Models\User;

#[WithEnv('DB_CONNECTION', 'testing')]
abstract class TestCase extends BaseTestCase
{
    use LazilyRefreshDatabase;
    use WithWorkbench;

    protected function getPackageProviders($app): array
    {
        $providers = [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            SchemasServiceProvider::class,
            FilamentCmsYoutubeServiceProvider::class,
            AdminPanelProvider::class,
        ];

        sort($providers);

        return $providers;
    }

    protected function defineEnvironment($app)
    {

        tap($app['config'], function (Repository $config) {
            $config->set('database.default', 'testing');
            $config->set('database.connections.testing', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);

            $config->set('auth.guards.testing.driver', 'session');
            $config->set('auth.guards.testing.provider', 'testing');
            $config->set('auth.providers.testing.driver', 'eloquent');
            $config->set('auth.providers.testing.model', User::class);

            $config->set('view.paths', [
                ...$config->get('view.paths'),
                __DIR__ . '/../resources/views',
            ]);
        });
    }
}
