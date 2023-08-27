<?php

namespace ArchiElite\LogViewer\Collections;

use ArchiElite\LogViewer\LogFile;
use ArchiElite\LogViewer\Readers\MultipleLogReader;
use Illuminate\Support\Collection;

/**
 * @var LogFile[] $items
 */
class LogFileCollection extends Collection
{
    public function sortByEarliestFirst(): self
    {
        $this->items = $this->sortBy(fn (LogFile $file) => $file->earliestTimestamp() . ($file->name ?? ''), SORT_NATURAL)->values()->toArray();

        return $this;
    }

    public function sortByLatestFirst(): self
    {
        $this->items = $this->sortByDesc(fn (LogFile $file) => $file->latestTimestamp() . ($file->name ?? ''), SORT_NATURAL)->values()->toArray();

        return $this;
    }

    public function latest(): ?LogFile
    {
        return $this->sortByDesc->latestTimestamp()->first();
    }

    public function earliest(): ?LogFile
    {
        return $this->sortBy->earliestTimestamp()->first();
    }

    public function logs(): MultipleLogReader
    {
        return new MultipleLogReader($this->items);
    }
}
