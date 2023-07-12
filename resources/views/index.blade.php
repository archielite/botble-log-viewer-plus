<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="shortcut icon" href="{{ asset('vendor/core/plugins/log-viewer-plus/images/log-viewer-32.png') }}">

        <title>Log Viewer{{ config('app.name') ? ' - ' . config('app.name') : '' }}</title>

        <link href="{{ asset('vendor/core/plugins/log-viewer-plus/css/app.css') }}" rel="stylesheet">
    </head>

    <body class="h-full px-3 lg:px-5 bg-gray-100 dark:bg-gray-900">
        <div id="log-viewer" class="flex h-full max-h-screen max-w-full">
            <router-view></router-view>
        </div>

        <script>
            window.LogViewer = @json($logViewerScriptVariables);
        </script>
        <script src="{{ asset('vendor/core/core/base/libraries/vue.global.min.js') }}"></script>
        <script src="{{ asset('vendor/core/plugins/log-viewer-plus/js/app.js') }}"></script>
    </body>
</html>
