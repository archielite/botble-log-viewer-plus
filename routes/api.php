<?php

use ArchiElite\LogViewer\Http\Controllers\FileController;
use ArchiElite\LogViewer\Http\Controllers\FolderController;
use ArchiElite\LogViewer\Http\Controllers\HostController;
use ArchiElite\LogViewer\Http\Controllers\LogController;
use ArchiElite\LogViewer\Http\Middleware\ForwardRequestToHostMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(array_merge(['web', 'auth'], config('plugins.log-viewer-plus.log-viewer.api_middleware')))
    ->prefix(config('plugins.log-viewer-plus.log-viewer.route_path') . '/api')
    ->name(config('plugins.log-viewer-plus.log-viewer.route_path') . '.')
    ->group(function () {
        Route::get('hosts', [HostController::class, 'index'])
            ->name('hosts');

        Route::middleware(ForwardRequestToHostMiddleware::class)->group(function () {
            Route::get('folders', [FolderController::class, 'index'])
                ->name('log-viewer.folders');
            Route::get('folders/{folderIdentifier}/download', [FolderController::class, 'download'])
                ->name('folders.download');
            Route::post('folders/{folderIdentifier}/clear-cache', [FolderController::class, 'clearCache'])
                ->name('folders.clear-cache');
            Route::delete('folders/{folderIdentifier}', [FolderController::class, 'delete'])
                ->name('folders.delete');

            Route::get('files', [FileController::class, 'index'])->name('log-viewer.files');
            Route::get('files/{fileIdentifier}/download', [FileController::class, 'download'])
                ->name('files.download');
            Route::post('files/{fileIdentifier}/clear-cache', [FileController::class, 'clearCache'])
                ->name('files.clear-cache');
            Route::delete('files/{fileIdentifier}', [FileController::class, 'delete'])
                ->name('files.delete');

            Route::post('clear-cache-all', [FileController::class, 'clearCacheAll'])
                ->name('files.clear-cache-all');
            Route::post('delete-multiple-files', [FileController::class, 'deleteMultipleFiles'])
                ->name('files.delete-multiple-files');

            Route::get('logs', [LogController::class, 'index'])
                ->name('logs');
        });
    });
