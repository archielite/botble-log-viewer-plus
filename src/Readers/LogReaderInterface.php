<?php

namespace ArchiElite\LogViewer\Readers;

use ArchiElite\LogViewer\LogFile;
use ArchiElite\LogViewer\Logs\Log;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LogReaderInterface
{
    public static function instance(LogFile $file): static;

    public static function clearInstance(LogFile $file): void;

    public static function clearInstances(): void;

    public function search(string $query = null): static;

    public function skip(int $number): static;

    public function limit(int $number): static;

    public function reverse(): static;

    public function forward(): static;

    public function setDirection(string $direction = null): static;

    public function getLevelCounts(): array;

    public function only($levels = null): static;

    public function setLevels($levels = null): static;

    public function allLevels(): static;

    public function except($levels = null): static;

    public function exceptLevels($levels = null): static;

    public function get(int $limit = null): array;

    public function next(): ?Log;

    public function paginate(int $perPage = 25, int $page = null): LengthAwarePaginator;

    public function total(): int;

    public function reset(): static;

    public function scan(int $maxBytesToScan = null, bool $force = false): static;

    public function numberOfNewBytes(): int;

    public function requiresScan(): bool;

    public function percentScanned(): int;
}
