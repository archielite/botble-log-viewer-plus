<?php

namespace ArchiElite\LogViewer\Events;

use ArchiElite\LogViewer\LogFile;
use Illuminate\Foundation\Events\Dispatchable;

class LogFileDeleted
{
    use Dispatchable;

    public function __construct(public LogFile $file)
    {
    }
}
