<?php

namespace ArchiElite\LogViewer\Logs;

use ArchiElite\LogViewer\LogTypeRegistrar;

class LogType implements \Stringable
{
    public const DEFAULT = 'log';

    public const LARAVEL = 'laravel';

    public const HTTP_ACCESS = 'http_access';

    public const HTTP_ERROR_APACHE = 'http_error_apache';

    public const HTTP_ERROR_NGINX = 'http_error_nginx';

    public const HORIZON_OLD = 'horizon_old';

    public const HORIZON = 'horizon';

    public const PHP_FPM = 'php_fpm';

    public const POSTGRES = 'postgres';

    public const REDIS = 'redis';

    public const SUPERVISOR = 'supervisor';

    public function __construct(public string $value)
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function name(): string
    {
        $class = $this->logClass();

        return match ($this->value) {
            self::LARAVEL => 'Laravel',
            self::HTTP_ACCESS => 'HTTP Access',
            self::HTTP_ERROR_APACHE => 'HTTP Error (Apache)',
            self::HTTP_ERROR_NGINX => 'HTTP Error (Nginx)',
            self::HORIZON_OLD => 'Horizon (Old)',
            self::HORIZON => 'Horizon',
            self::PHP_FPM => 'PHP-FPM',
            self::POSTGRES => 'Postgres',
            self::REDIS => 'Redis',
            self::SUPERVISOR => 'Supervisor',
            default => isset($class) ? ($class::$name ?? 'Unknown') : 'Unknown',
        };
    }

    /**
     * @return string|Log|null
     */
    public function logClass(): ?string
    {
        return app(LogTypeRegistrar::class)->getClass($this->value);
    }

    public function isUnknown(): bool
    {
        return $this->value === static::DEFAULT;
    }
}
