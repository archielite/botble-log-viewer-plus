<?php

namespace ArchiElite\LogViewer\Http\Controllers;

use ArchiElite\LogViewer\Facades\LogViewer;
use ArchiElite\LogViewer\Utils\Utils;
use Botble\Base\Http\Controllers\BaseController;
use Illuminate\Contracts\View\View;

class LogViewerController extends BaseController
{
    public function __invoke(): View
    {
        return view(LogViewer::getViewLayout(), [
            'logViewerScriptVariables' => [
                'headers' => (object) [],
                'version' => LogViewer::version(),
                'app_name' => setting('admin_title') ?: config('app.name'),
                'path' => config('plugins.log-viewer-plus.log-viewer.route_path'),
                'back_to_system_url' => route('dashboard.index'),
                'back_to_system_label' => config('plugins.log-viewer-plus.log-viewer.back_to_system_label'),
                'max_log_size_formatted' => Utils::bytesForHumans(LogViewer::maxLogSize()),
                'show_support_link' => config('plugins.log-viewer-plus.log-viewer.show_support_link', true),
                'supports_hosts' => LogViewer::supportsHostsFeature(),
                'hosts' => LogViewer::getHosts(),
                'empty_state_image' => asset('vendor/core/plugins/log-viewer-plus/images/empty-state.png'),
            ],
        ]);
    }
}
