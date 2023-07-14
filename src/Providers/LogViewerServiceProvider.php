<?php

namespace ArchiElite\LogViewer\Providers;

use ArchiElite\LogViewer\Commands\GenerateDummyLogsCommand;
use ArchiElite\LogViewer\LogViewerService;
use Botble\Base\Facades\DashboardMenu;
use Botble\Base\Supports\ServiceProvider;
use Botble\Base\Traits\LoadAndPublishDataTrait;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Cache;

class LogViewerServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function register(): void
    {
        $this->app->bind('log-viewer', LogViewerService::class);
        $this->app->bind('log-viewer-cache', function () {
            return Cache::driver(config('plugins.log-viewer-plus.log-viewer.cache_driver'));
        });
    }

    public function boot(): void
    {
        $this->setNamespace('plugins/log-viewer-plus')
            ->loadAndPublishConfigurations(['log-viewer'])
            ->loadRoutes(['api', 'web'])
            ->publishAssets()
            ->loadAndPublishTranslations()
            ->loadAndPublishViews();

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateDummyLogsCommand::class,
            ]);
        }

        $this->app['events']->listen(RouteMatched::class, function () {
            DashboardMenu::registerItem([
                'id' => 'cms-plugin-log-viewer',
                'priority' => 7,
                'parent_id' => 'cms-core-platform-administration',
                'name' => 'plugins/log-viewer-plus::log-viewer.name',
                'icon' => null,
                'url' => route('log-viewer.index'),
                'permissions' => ['log-viewer.index'],
            ]);
        });
    }
}
