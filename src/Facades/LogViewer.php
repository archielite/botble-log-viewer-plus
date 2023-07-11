<?php

namespace ArchiElite\LogViewer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ArchiElite\LogViewer\LogViewerService
 */
class LogViewer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'log-viewer';
    }
}
