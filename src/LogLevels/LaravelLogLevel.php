<?php

namespace ArchiElite\LogViewer\LogLevels;

class LaravelLogLevel implements LevelInterface
{
    public const Debug = 'DEBUG';

    public const Info = 'INFO';

    public const Notice = 'NOTICE';

    public const Warning = 'WARNING';

    public const Error = 'ERROR';

    public const Critical = 'CRITICAL';

    public const Alert = 'ALERT';

    public const Emergency = 'EMERGENCY';

    public const None = '';

    public string $value;

    public function __construct(string $value = null)
    {
        $this->value = $value ?? self::None;
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
            self::None,
        ];
    }

    public static function from(string $value = null): self
    {
        return new self($value);
    }

    public function getName(): string
    {
        return match ($this->value) {
            self::None => 'None',
            default => ucfirst(strtolower($this->value)),
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
