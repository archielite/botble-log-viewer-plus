<?php

namespace ArchiElite\LogViewer\LogLevels;

class NginxStatusLevel implements LevelInterface
{
    public const Debug = 'debug';

    public const Info = 'info';

    public const Notice = 'notice';

    public const Warning = 'warn';

    public const Error = 'error';

    public const Critical = 'crit';

    public const Alert = 'alert';

    public const Emergency = 'emerg';

    public function __construct(public string $value = self::Error)
    {
    }

    public static function cases(): array
    {
        return [
            self::Debug,
            self::Info,
            self::Notice,
            self::Warning,
            self::Error,
            self::Critical,
            self::Alert,
            self::Emergency,
        ];
    }

    public static function from(string $value = null): self
    {
        return new self($value);
    }

    public function getName(): string
    {
        return match ($this->value) {
            self::Warning => 'Warning',
            self::Critical => 'Critical',
            self::Emergency => 'Emergency',
            default => ucfirst($this->value),
        };
    }

    public function getClass(): LevelClass
    {
        return match ($this->value) {
            self::Debug, self::Info, self::Notice => LevelClass::info(),
            self::Warning => LevelClass::warning(),
            self::Error, self::Critical, self::Alert, self::Emergency => LevelClass::danger(),
            default => LevelClass::none(),
        };
    }

    public static function caseValues(): array
    {
        return self::cases();
    }
}
