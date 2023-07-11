<?php

namespace ArchiElite\LogViewer\Http\Middleware;

use ArchiElite\LogViewer\Facades\LogViewer;

class AuthorizeLogViewer
{
    public function handle($request, $next)
    {
        LogViewer::auth();

        return $next($request);
    }
}
