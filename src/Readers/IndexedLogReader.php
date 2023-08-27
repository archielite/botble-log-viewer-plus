<?php

namespace ArchiElite\LogViewer\Readers;

use ArchiElite\LogViewer\Concerns;
use ArchiElite\LogViewer\Exceptions\SkipLineException;
use ArchiElite\LogViewer\Facades\LogViewer;
use ArchiElite\LogViewer\LevelCount;
use ArchiElite\LogViewer\LogIndex;
use ArchiElite\LogViewer\Logs\Log;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as PaginationLengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class IndexedLogReader extends BaseLogReader implements LogReaderInterface
{
    use Concerns\LogReader\CanFilterUsingIndex;
    use Concerns\LogReader\CanSetDirectionUsingIndex;

    protected LogIndex $logIndex;

    protected bool $lazyScanning = false;

    protected int $mtimeBeforeScan;

    protected function onFileOpened(): void
    {
        if ($this->requiresScan() && ! $this->lazyScanning) {
            $this->scan();
        } else {
            $this->reset();
        }
    }

    protected function index(): LogIndex
    {
        return $this->file->index($this->query);
    }

    public function lazyScanning($lazy = true): static
    {
        $this->lazyScanning = $lazy;

        return $this;
    }

    public function scan(int $maxBytesToScan = null, bool $force = false): static
    {
        if (is_null($maxBytesToScan)) {
            $maxBytesToScan = LogViewer::lazyScanChunkSize();
        }

        if (! $this->requiresScan() && ! $force) {
            return $this;
        }

        if ($this->numberOfNewBytes() < 0) {
            $force = true;
        }

        if ($force) {
            $this->index()->clearCache();
        }

        $this->prepareFileForReading();

        $stopScanningAfter = microtime(true) + LogViewer::lazyScanTimeout();
        $this->mtimeBeforeScan = $this->file->mtime();

        $logIndex = $this->index();
        $earliest_timestamp = $this->file->getMetadata('earliest_timestamp');
        $latest_timestamp = $this->file->getMetadata('latest_timestamp');
        $currentLog = '';
        $currentLogLevel = '';
        $currentTimestamp = null;
        $currentIndex = $this->index()->getLastScannedIndex();
        fseek($this->fileHandle, $this->index()->getLastScannedFilePosition());
        $currentLogPosition = ftell($this->fileHandle);
        $lastPositionToScan = isset($maxBytesToScan) ? ($currentLogPosition + $maxBytesToScan) : null;

        while (
            (! isset($lastPositionToScan) || $currentLogPosition < $lastPositionToScan)
            && ($stopScanningAfter > microtime(true))
            && ($line = fgets($this->fileHandle, 1024)) !== false
        ) {
            $matches = [];
            $ts = null;
            $lvl = null;

            try {
                $lineMatches = $this->logClass::matches(trim($line), $ts, $lvl);
            } catch (SkipLineException) {
                continue;
            }

            if ($lineMatches) {
                if ($currentLog !== '') {
                    if (is_null($this->query) || preg_match($this->query, $currentLog)) {
                        $logIndex->addToIndex($currentLogPosition, $currentTimestamp ?? 0, $currentLogLevel, $currentIndex);
                    }

                    $currentLog = '';
                    ++$currentIndex;
                }

                $currentTimestamp = $ts;
                $earliest_timestamp = min($earliest_timestamp ?? $currentTimestamp, $currentTimestamp);
                $latest_timestamp = max($latest_timestamp ?? $currentTimestamp, $currentTimestamp);
                $currentLogPosition = ftell($this->fileHandle) - strlen($line);
                $currentLogLevel = $lvl;

                $currentLog = $line;
            } elseif ($currentLog !== '') {
                $currentLog .= $line;
            }
        }

        if ($currentLog !== '' && $this->logClass::matches($currentLog)) {
            if ((is_null($this->query) || preg_match($this->query, $currentLog))) {
                $logIndex->addToIndex($currentLogPosition, $currentTimestamp ?? 0, $currentLogLevel, $currentIndex);
                ++$currentIndex;
            }
        }

        $logIndex->setLastScannedIndex($currentIndex);
        $logIndex->setLastScannedFilePosition(ftell($this->fileHandle));
        $logIndex->save();

        $this->file->setMetadata('name', $this->file->name);
        $this->file->setMetadata('path', $this->file->path);
        $this->file->setMetadata('size', $this->file->size());
        $this->file->setMetadata('earliest_timestamp', $this->index()->getEarliestTimestamp());
        $this->file->setMetadata('latest_timestamp', $this->index()->getLatestTimestamp());
        $this->file->setMetadata('last_scanned_file_position', ftell($this->fileHandle));
        $this->file->addRelatedIndex($logIndex);

        $this->file->saveMetadata();

        rewind($this->fileHandle);

        return $this->reset();
    }

    public function reset(): static
    {
        $this->index()->reset();

        return $this;
    }

    public function getLevelCounts(): array
    {
        $this->prepareFileForReading();
        $this->logClass::levelClass();

        return $this->index()->getLevelCounts()->map(fn (int $count, string $level) => new LevelCount(
            $this->levelClass::from($level),
            $count,
            $this->index()->isLevelSelected($level),
        ))->sortBy(static fn(LevelCount $levelCount) => $levelCount->level->getName(), SORT_NATURAL)->toArray();
    }

    public function get(int $limit = null): array
    {
        if (! is_null($limit) && method_exists($this, 'limit')) {
            $this->limit($limit);
        }

        $logs = [];

        while ($log = $this->next()) {
            $logs[] = $log;
        }

        return $logs;
    }

    public function next(): ?Log
    {
        $this->prepareFileForReading();

        [$index, $position] = $this->index()->next();

        if (is_null($index)) {
            return null;
        }

        $text = $this->getLogTextAtPosition($position);

        if (empty($text)) {
            return null;
        }

        return $this->makeLog($text, $position, $index);
    }

    public function total(): int
    {
        return $this->index()->count();
    }

    public function paginate(int $perPage = 25, int $page = null): PaginationLengthAwarePaginator
    {
        $page = $page ?: Paginator::resolveCurrentPage('page');

        if (! is_null($this->onlyShowIndex)) {
            return new LengthAwarePaginator(
                [$this->reset()->getLogAtIndex($this->onlyShowIndex)],
                1,
                $perPage,
                $page
            );
        }

        $this->reset()->skip(max(0, $page - 1) * $perPage);

        return new LengthAwarePaginator(
            $this->get($perPage),
            $this->total(),
            $perPage,
            $page
        );
    }

    public function numberOfNewBytes(): int
    {
        $lastScannedFilePosition = $this->file->getLastScannedFilePositionForQuery($this->query);

        if (is_null($lastScannedFilePosition)) {
            $lastScannedFilePosition = $this->index()->getLastScannedFilePosition();
        }

        return $this->file->size() - $lastScannedFilePosition;
    }

    public function requiresScan(): bool
    {
        if (isset($this->mtimeBeforeScan) && ($this->file->mtime() > $this->mtimeBeforeScan || $this->file->mtime() === time())) {
            return $this->numberOfNewBytes() >= LogViewer::lazyScanChunkSize();
        }

        return $this->numberOfNewBytes() !== 0;
    }

    public function percentScanned(): int
    {
        if ($this->file->size() <= 0) {
            // empty file, so assume it has been fully scanned.
            return 100;
        }

        return 100 - intval(($this->numberOfNewBytes() / $this->file->size() * 100));
    }

    protected function getLogAtIndex(int $index): ?Log
    {
        $position = $this->index()->getPositionForIndex($index);

        $text = $this->getLogTextAtPosition($position);

        if ($text === '') {
            return null;
        }

        return $this->makeLog($text, $position, $index);
    }

    protected function getLogTextAtPosition(int $position): ?string
    {
        $this->prepareFileForReading();

        fseek($this->fileHandle, $position, SEEK_SET);

        $currentLog = '';

        while (($line = fgets($this->fileHandle)) !== false) {
            if ($this->logClass::matches($line)) {
                if ($currentLog !== '') {
                    break;
                }
            } elseif ($currentLog === '') {
                continue;
            }

            $currentLog .= $line;
        }

        return $currentLog;
    }
}
