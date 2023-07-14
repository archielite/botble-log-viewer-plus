<?php

use ArchiElite\LogViewer\Http\Controllers\LogViewerController;
use Botble\Base\Facades\BaseHelper;
use Illuminate\Support\Facades\Route;

Route::middleware(array_merge(['web', 'auth'], config('plugins.log-viewer-plus.log-viewer.middleware', [])))
    ->prefix(BaseHelper::getAdminPrefix(). '/' .config('plugins.log-viewer-plus.log-viewer.route_path'))
    ->name(config('plugins.log-viewer-plus.log-viewer.route_path') . '.')
    ->group(function () {
        Route::get('/{view?}', [LogViewerController::class, '__invoke'])
            ->where('view', '(.*)')
            ->name('index');
    });

