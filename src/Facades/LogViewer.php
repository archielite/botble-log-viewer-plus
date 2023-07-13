<?php

namespace ArchiElite\LogViewer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string basePathForLogs()
 * @method static \ArchiElite\LogViewer\Collections\LogFileCollection getFiles()
 * @method static \ArchiElite\LogViewer\Collections\LogFolderCollection getFilesGroupedByFolder()
 * @method static \ArchiElite\LogViewer\LogFile|null getFile(string|null $fileIdentifier)
 * @method static \ArchiElite\LogViewer\LogFolder|null getFolder(string|null $folderIdentifier)
 * @method static bool supportsHostsFeature()
 * @method static void resolveHostsUsing(callable $callback)
 * @method static \ArchiElite\LogViewer\HostCollection getHosts()
 * @method static \ArchiElite\LogViewer\Host|null getHost(string|null $hostIdentifier)
 * @method static void clearFileCache()
 * @method static string getRoutePrefix()
 * @method static array getRouteMiddleware()
 * @method static void auth($callback = null)
 * @method static int lazyScanChunkSize()
 * @method static int maxLogSize()
 * @method static void setMaxLogSize(int $bytes)
 * @method static string laravelRegexPattern()
 * @method static string logMatchPattern()
 * @method static string version()
 *
 * @see \ArchiElite\LogViewer\LogViewerService
 */
class LogViewer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'log-viewer';
    }
}
