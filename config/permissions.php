<?php

return [
    [
        'name' => 'Log Viewer',
        'flag' => 'log-viewer.index',
        'parent_flag' => 'core.system',
    ],
    [
        'name' => 'Download',
        'flag' => 'log-viewer.download',
        'parent_flag' => 'log-viewer.index',
    ],
    [
        'name' => 'Delete',
        'flag' => 'log-viewer.destroy',
        'parent_flag' => 'log-viewer.index',
    ],
];
