<?php

namespace ArchiElite\LogViewer\Utils;

use ArchiElite\LogViewer\Facades\LogViewer;
use ArchiElite\LogViewer\LogFile;
use ArchiElite\LogViewer\LogIndex;

class GenerateCacheKey
{
    public static function for(mixed $object, ?string $namespace = null): string
    {
        $key = '';

        if ($object instanceof LogFile) {
            $key = sprintf('%s:file:%s', self::baseKey(), $object->identifier);
        }

        if ($object instanceof LogIndex) {
            $key = sprintf('%s:%s', self::for($object->file), $object->identifier);
        }

        if (is_string($object)) {
            $key = sprintf('%s:%s', self::baseKey(), $object);
        }

        if (! empty($namespace)) {
            $key .= sprintf(':%s', $namespace);
        }

        return $key;
    }

    protected static function baseKey(): string
    {
        return sprintf('lv:%s', LogViewer::version());
    }
}
