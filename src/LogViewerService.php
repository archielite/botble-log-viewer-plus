<?php

namespace ArchiElite\LogViewer;

use ArchiElite\LogViewer\Collections\LogFileCollection;
use ArchiElite\LogViewer\Collections\LogFolderCollection;
use ArchiElite\LogViewer\Readers\IndexedLogReader;
use ArchiElite\LogViewer\Readers\LogReaderInterface;
use ArchiElite\LogViewer\Utils\Utils;
use GuzzleHttp\Client;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LogViewerService
{
    public const DEFAULT_MAX_LOG_SIZE_TO_DISPLAY = 131_072;    // 128 KB

    public static string $logFileClass = LogFile::class;

    public static string $logReaderClass = IndexedLogReader::class;

    protected ?Collection $_cachedFiles = null;

    protected mixed $authCallback;

    protected int $maxLogSizeToDisplay = self::DEFAULT_MAX_LOG_SIZE_TO_DISPLAY;

    protected mixed $hostsResolver;

    protected string $layout = 'plugins/log-viewer-plus::index';

    protected function getLaravelLogFilePaths(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $baseDir = str_replace(
                ['[', ']'],
                ['{LEFTBRACKET}', '{RIGHTBRACKET}'],
                str_replace('\\', '/', $this->basePathForLogs())
            );
            $baseDir = str_replace(
                ['{LEFTBRACKET}', '{RIGHTBRACKET}'],
                ['[[]', '[]]'],
                $baseDir
            );
        } else {
            $baseDir = str_replace(
                ['*', '?', '\\', '[', ']'],
                ['\*', '\?', '\\\\', '\[', '\]'],
                $this->basePathForLogs()
            );
        }
        $files = [];

        foreach (config('plugins.log-viewer-plus.log-viewer.include_files', []) as $pattern) {
            if (! str_starts_with($pattern, DIRECTORY_SEPARATOR)) {
                $pattern = $baseDir . $pattern;
            }

            $files = array_merge($files, $this->getFilePathsMatchingPattern($pattern));
        }

        foreach (config('plugins.log-viewer-plus.log-viewer.exclude_files', []) as $pattern) {
            if (! str_starts_with($pattern, DIRECTORY_SEPARATOR)) {
                $pattern = $baseDir . $pattern;
            }

            $files = array_diff($files, $this->getFilePathsMatchingPattern($pattern));
        }

        $files = array_map('realpath', $files);

        $files = array_filter($files, 'is_file');

        return array_values(array_reverse($files));
    }

    protected function getFilePathsMatchingPattern($pattern): array|false
    {
        if (str_contains($pattern, '**')) {
            return Utils::glob_recursive($pattern);
        }

        return glob($pattern) ?: [];
    }

    public function basePathForLogs(): string
    {
        return Str::finish(realpath(storage_path('logs')), DIRECTORY_SEPARATOR);
    }

    public function getFiles(): LogFileCollection
    {
        if (! isset($this->_cachedFiles)) {
            $fileClass = static::$logFileClass;

            $this->_cachedFiles = (new LogFileCollection($this->getLaravelLogFilePaths()))
                ->unique()
                ->map(fn ($filePath) => new $fileClass($filePath))
                ->values();
        }

        if (config('log-viewer.hide_unknown_files', true)) {
            $this->_cachedFiles = $this->_cachedFiles->filter(fn (LogFile $file) => ! $file->type()->isUnknown());
        }

        return $this->_cachedFiles;
    }

    public function getFilesGroupedByFolder(): LogFolderCollection
    {
        return LogFolderCollection::fromFiles($this->getFiles());
    }

    public function getFile(?string $fileIdentifier): ?LogFile
    {
        if (empty($fileIdentifier)) {
            return null;
        }

        $file = $this->getFiles()
            ->where('identifier', $fileIdentifier)
            ->first();

        if (! $file) {
            $file = $this->getFiles()
                ->where('name', $fileIdentifier)
                ->first();
        }

        return $file;
    }

    public function getFolder(?string $folderIdentifier): ?LogFolder
    {
        return $this->getFilesGroupedByFolder()
            ->first(fn (LogFolder $folder) => (empty($folderIdentifier) && $folder->isRoot())
                || $folder->identifier === $folderIdentifier
                || $folder->path === $folderIdentifier);
    }

    public function supportsHostsFeature(): bool
    {
        return class_exists(Client::class);
    }

    public function resolveHostsUsing(callable $callback): void
    {
        $this->hostsResolver = $callback;
    }

    public function getHosts(): HostCollection
    {
        $hosts = HostCollection::fromConfig(config('plugins.log-viewer-plus.log-viewer.hosts', []));

        if (isset($this->hostsResolver)) {
            $hosts = new HostCollection(
                call_user_func($this->hostsResolver, $hosts) ?? []
            );

            $hosts->transform(fn ($host, $key) => is_array($host)
                ? Host::fromConfig($key, $host)
                : $host);
        }

        return $hosts->values();
    }

    public function getHost(?string $hostIdentifier): ?Host
    {
        return $this->getHosts()
            ->first(fn (Host $host) => $host->identifier === $hostIdentifier);
    }

    public function clearFileCache(): void
    {
        $this->_cachedFiles = null;
    }

    public function getRoutePrefix(): string
    {
        return config('plugins.log-viewer-plus.log-viewer.route_path', 'log-viewer');
    }

    public function getRouteMiddleware(): array
    {
        return config('plugins.log-viewer-plus.log-viewer.middleware', []) ?: ['web'];
    }

    public function auth($callback = null): void
    {
        if (is_null($callback) && isset($this->authCallback)) {
            $canViewLogViewer = call_user_func($this->authCallback, request());

            if (! $canViewLogViewer) {
                throw new AuthorizationException('Unauthorized.');
            }
        } elseif (is_null($callback)) {
            if (! (Auth::check() && Auth::user()->hasPermission('log-viewer.index'))) {
                throw new AuthorizationException('Unauthorized.');
            }
        } elseif (is_callable($callback)) {
            $this->authCallback = $callback;
        }
    }

    public function lazyScanChunkSize(): int
    {
        return intval(config('plugins.log-viewer-plus.log-viewer.lazy_scan_chunk_size_in_mb', 100)) * 1024 * 1024;
    }

    public function maxLogSize(): int
    {
        return $this->maxLogSizeToDisplay;
    }

    public function lazyScanTimeout(): float
    {
        return 5.0;
    }

    public function setMaxLogSize(int $bytes): void
    {
        $this->maxLogSizeToDisplay = $bytes > 0 ? $bytes : self::DEFAULT_MAX_LOG_SIZE_TO_DISPLAY;
    }

    public function extend(string $type, string $class): void
    {
        app(LogTypeRegistrar::class)->register($type, $class);
    }

    public function useLogFileClass(string $class): void
    {
        if (! is_subclass_of($class, LogFile::class)) {
            throw new \InvalidArgumentException("The class {$class} must extend from the " . LogFile::class . ' class.');
        }

        static::$logFileClass = $class;
    }

    public function useLogReaderClass(string $class): void
    {
        $reflection = new \ReflectionClass($class);

        if (! $reflection->implementsInterface(LogReaderInterface::class)) {
            throw new \InvalidArgumentException("The class {$class} must implement the LogReaderInterface.");
        }

        static::$logReaderClass = $class;
    }

    public function logReaderClass(): string
    {
        return static::$logReaderClass;
    }

    public function setViewLayout(string $layout): void
    {
        $this->layout = $layout;
    }

    public function getViewLayout(): string
    {
        return $this->layout;
    }

    public function version(): string
    {
        return Cache::remember('log-viewer-version', 60 * 60 * 24, function () {
            $content = File::get(__DIR__ . '/../plugin.json');

            $content = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            return Arr::get($content, 'version');
        });
    }
}
