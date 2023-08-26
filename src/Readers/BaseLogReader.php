<?php

namespace ArchiElite\LogViewer\Readers;

use ArchiElite\LogViewer\Concerns;
use ArchiElite\LogViewer\LogFile;
use ArchiElite\LogViewer\LogLevels\LevelInterface;
use ArchiElite\LogViewer\Logs\Log;

abstract class BaseLogReader
{
    use Concerns\LogReader\KeepsInstances;
    use Concerns\LogReader\KeepsFileHandle;

    /** @var string|Log */
    protected string $logClass;

    /** @var string|LevelInterface */
    protected string $levelClass;

    public function __construct(protected LogFile $file)
    {
        $this->logClass = $this->file->type()->logClass() ?? Log::class;
        $this->levelClass = $this->logClass::levelClass();
    }

    protected function makeLog(string $text, int $filePosition, int $index): Log
    {
        return new $this->logClass($text, $this->file->identifier, $filePosition, $index);
    }

    public function __destruct()
    {
        $this->closeFile();
    }
}
