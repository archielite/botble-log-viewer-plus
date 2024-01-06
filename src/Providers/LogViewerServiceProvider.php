<?php

/** @noinspection PhpUndefinedNamespaceInspection */
/** @noinspection PhpUndefinedClassInspection */

namespace ArchiElite\LogViewer\Providers;

use ArchiElite\LogViewer\Commands\GenerateDummyLogsCommand;
use ArchiElite\LogViewer\LogTypeRegistrar;
use ArchiElite\LogViewer\LogViewerService;
use Botble\Base\Facades\DashboardMenu;
use Botble\Base\Facades\PanelSectionManager;
use Botble\Base\PanelSections\Manager;
use Botble\Base\PanelSections\PanelSectionItem;
use Botble\Base\PanelSections\System\SystemPanelSection;
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
        $this->app->bind('log-viewer-cache', fn () => Cache::driver(config('plugins.log-viewer-plus.log-viewer.cache_driver')));

        if (! $this->app->bound(LogTypeRegistrar::class)) {
            $this->app->singleton(LogTypeRegistrar::class, fn () => new LogTypeRegistrar());
        }
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

        if (version_compare('7.0.0', get_core_version(), '>=')) {
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
        } else {
            PanelSectionManager::group('system')->beforeRendering(function (Manager $manager) {
                $manager
                    ->registerItem(
                        SystemPanelSection::class,
                        fn() => PanelSectionItem::make('system.log-viewer')
                            ->setTitle(trans('plugins/log-viewer-plus::log-viewer.name'))
                            ->withDescription(trans('plugins/log-viewer-plus::log-viewer.description'))
                            ->withIcon('ti ti-report')
                            ->withPriority(9990)
                            ->withRoute('log-viewer.index')
                    );
            });
        }
    }
}
