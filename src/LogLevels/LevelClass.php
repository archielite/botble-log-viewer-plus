<?php

namespace ArchiElite\LogViewer\LogLevels;

class LevelClass
{
    public const SUCCESS = 'success';

    public const INFO = 'info';

    public const WARNING = 'warning';

    public const DANGER = 'danger';

    public const NONE = 'none';

    public function __construct(
        public string $value,
    ) {
    }

    public static function from(string $value = null): LevelClass
    {
        return new static($value);
    }

    public static function caseValues(): array
    {
        return [
            static::SUCCESS,
            static::INFO,
            static::WARNING,
            static::DANGER,
            static::NONE,
        ];
    }

    public static function success(): static
    {
        return new static(static::SUCCESS);
    }

    public static function info(): static
    {
        return new static(static::INFO);
    }

    public static function warning(): static
    {
        return new static(static::WARNING);
    }

    public static function danger(): static
    {
        return new static(static::DANGER);
    }

    public static function none(): static
    {
        return new static(static::NONE);
    }
}
