<?php

namespace ArchiElite\LogViewer\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class LogFolderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'identifier' => $this->identifier,
            'path' => $this->path,
            'clean_path' => $this->cleanPath(),
            'is_root' => $this->isRoot(),
            'earliest_timestamp' => $this->earliestTimestamp(),
            'latest_timestamp' => $this->latestTimestamp(),
            'download_url' => $this->downloadUrl(),
            'files' => LogFileResource::collection($this->files()),
            'can_download' => Auth::user()->hasPermission('log-viewer.index'),
            'can_delete' => Auth::user()->hasPermission('log-viewer.destroy'),
            'loading' => false,
        ];
    }
}
