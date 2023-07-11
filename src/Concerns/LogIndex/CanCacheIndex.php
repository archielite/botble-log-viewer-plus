<?php

namespace ArchiElite\LogViewer\Concerns\LogIndex;

use ArchiElite\LogViewer\Facades\Cache;
use ArchiElite\LogViewer\LogIndexChunk;
use ArchiElite\LogViewer\Utils\GenerateCacheKey;
use Carbon\Carbon;
use Carbon\CarbonInterface;

trait CanCacheIndex
{
    public function clearCache(): void
    {
        $this->clearChunksFromCache();

        Cache::forget($this->metaCacheKey());
        Cache::forget($this->cacheKey());

        $this->loadMetadata();
    }

    protected function saveMetadataToCache(): void
    {
        Cache::put($this->metaCacheKey(), $this->getMetadata(), $this->cacheTtl());
    }

    protected function getMetadataFromCache(): array
    {
        return Cache::get($this->metaCacheKey(), []);
    }

    protected function saveChunkToCache(LogIndexChunk $chunk): void
    {
        Cache::put(
            $this->chunkCacheKey($chunk->index),
            $chunk->data,
            $this->cacheTtl()
        );
    }

    protected function getChunkDataFromCache(int $index, $default = null): ?array
    {
        return Cache::get($this->chunkCacheKey($index), $default);
    }

    protected function clearChunksFromCache(): void
    {
        foreach ($this->getChunkDefinitions() as $chunkDefinition) {
            Cache::forget($this->chunkCacheKey($chunkDefinition['index']));
        }
    }

    protected function cacheKey(): string
    {
        return GenerateCacheKey::for($this);
    }

    protected function metaCacheKey(): string
    {
        return GenerateCacheKey::for($this, 'metadata');
    }

    protected function chunkCacheKey(int $index): string
    {
        return GenerateCacheKey::for($this, "chunk:$index");
    }

    protected function cacheTtl(): CarbonInterface
    {
        if (! empty($this->query)) {
            return Carbon::now()->addDay();
        }

        return Carbon::now()->addWeek();
    }
}
