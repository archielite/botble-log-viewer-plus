<?php

namespace ArchiElite\LogViewer\Utils;

class Benchmark
{
    public static array $tests = [];

    public static function time(string $name): void
    {
        if (! array_key_exists($name, static::$tests)) {
            static::$tests[$name] = [
                'current' => [
                    'start' => null,
                    'end' => null,
                ],
                'history' => [],
            ];
        }

        static::$tests[$name]['current'] = [
            'start' => microtime(true),
        ];
    }

    public static function start(string $name): void
    {
        static::time($name);
    }

    public static function endTime(string $name): float
    {
        static::$tests[$name]['current']['end'] = microtime(true);

        $current = static::$tests[$name]['current'];

        static::$tests[$name]['history'][] = array_merge($current, [
            'duration' => $current['end'] - $current['start'],
        ]);

        return $current['end'] - $current['start'];
    }

    public static function end(string $name): float
    {
        return static::endTime($name);
    }

    public static function getTotal(string $name): float
    {
        $history = static::$tests[$name]['history'];

        return array_reduce($history, fn ($sum, $historyEntry) => $sum + $historyEntry['duration'], 0);
    }

    public static function getAverage(string $name): float
    {
        return static::getTotal($name) / (is_countable(static::$tests[$name]['history']) ? count(static::$tests[$name]['history']) : 0);
    }

    public static function dd(string $name = null): void
    {
        self::dump($name);

        exit();
    }

    public static function dump(string $name = null): void
    {
        if ($name) {
            dump(self::results($name));

            return;
        }

        foreach (self::results() as $result) {
            dump($result);
        }
    }

    public static function results(string $name = null): array
    {
        if ($name) {
            $testData = static::$tests[$name];

            return [
                'name' => $name,
                'number_of_runs' => is_countable($testData['history']) ? count($testData['history']) : 0,
                'total' => number_format(static::getTotal($name), 6),
                'average' => number_format(static::getAverage($name), 6),
            ];
        }

        $results = [];

        foreach (static::$tests as $testName => $testData) {
            $results[] = [
                'name' => $testName,
                'number_of_runs' => is_countable($testData['history']) ? count($testData['history']) : 0,
                'total' => number_format(static::getTotal($testName), 6),
                'average' => number_format(static::getAverage($testName), 6),
            ];
        }

        return $results;
    }
}
