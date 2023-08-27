<?php

namespace ArchiElite\LogViewer\Utils;

use ArchiElite\LogViewer\Exceptions\InvalidRegularExpression;

class Utils
{
    public static function bytesForHumans(int $bytes): string
    {
        if ($bytes > ($gb = 1024 * 1024 * 1024)) {
            return sprintf('%s GB', number_format($bytes / $gb, 2));
        } elseif ($bytes > ($mb = 1024 * 1024)) {
            return sprintf('%s MB', number_format($bytes / $mb, 2));
        } elseif ($bytes > ($kb = 1024)) {
            return sprintf('%s KB', number_format($bytes / $kb, 2));
        }

        return "$bytes bytes";
    }

    public static function validateRegex(string $regexString, bool $throw = true): bool
    {
        $error = null;
        set_error_handler(function (int $errno, string $errstr) use (&$error) {
            $error = $errstr;

            return true;
        }, E_WARNING);
        preg_match($regexString, '');
        restore_error_handler();

        if (! empty($error)) {
            $error = str_replace('preg_match(): ', '', $error);

            if ($throw) {
                throw new InvalidRegularExpression($error);
            }

            return false;
        }

        return true;
    }

    public static function shortMd5(string $content, int $length = 8): string
    {
        if ($length > 32) {
            $length = 32;
        }

        return substr(md5($content), -$length, $length);
    }

    public static function glob_recursive($pattern, $flags = 0): array
    {
        $files = glob($pattern, $flags) ?: [];
        $folders = glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) ?: [];

        foreach ($folders as $dir) {
            $files = array_merge($files, static::glob_recursive($dir . '/' . basename($pattern), $flags));
        }

        return $files;
    }
}
