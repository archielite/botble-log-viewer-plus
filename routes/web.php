<?php

use ArchiElite\LogViewer\Http\Controllers\LogViewerController;
use Illuminate\Support\Facades\Route;

Route::middleware(config('plugins.log-viewer-plus.log-viewer.middleware'))
    ->prefix(config('plugins.log-viewer-plus.log-viewer.route_path'))
    ->name(config('plugins.log-viewer-plus.log-viewer.route_path') . '.')
    ->group(function () {
        Route::get('/{view?}', [LogViewerController::class, '__invoke'])
            ->where('view', '(.*)')
            ->name('index');
    });

