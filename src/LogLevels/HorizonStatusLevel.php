<?php

namespace ArchiElite\LogViewer\LogLevels;

class HorizonStatusLevel implements LevelInterface
{
    public function __construct(public string $value)
    {
    }

    public static function from(string $value = null): LevelInterface
    {
        return new static($value);
    }

    public static function caseValues(): array
    {
        return [
            'Processing',
            'Running',
            'Processed',
            'Done',
            'Failed',
            'Fail',
        ];
    }

    public function getName(): string
    {
        return ucfirst(strtolower($this->value));
    }

    public function getClass(): LevelClass
    {
        return match (strtolower($this->value)) {
            'processing', 'running' => LevelClass::info(),
            'processed', 'done' => LevelClass::success(),
            'failed', 'fail' => LevelClass::danger(),
            default => LevelClass::none(),
        };
    }
}
