<?php

namespace ArchiElite\LogViewer\Http\Resources;

use ArchiElite\LogViewer\LogFile;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin LogFile
 */
class LogFileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'type' => [
                'value' => $this->type()->value,
                'name' => $this->type()->name(),
            ],
            'identifier' => $this->identifier,
            'sub_folder' => $this->subFolder,
            'sub_folder_identifier' => $this->subFolderIdentifier(),
            'path' => $this->path,
            'name' => $this->name,
            'size' => $this->size(),
            'size_in_mb' => $this->sizeInMB(),
            'size_formatted' => $this->sizeFormatted(),
            'download_url' => $this->downloadUrl(),
            'earliest_timestamp' => $this->earliestTimestamp(),
            'latest_timestamp' => $this->latestTimestamp(),
            'can_download' => Auth::user()->hasPermission('log-viewer.index'),
            'can_delete' => Auth::user()->hasPermission('log-viewer.destroy'),
            'loading' => false, // helper for frontend
            'selected_for_deletion' => false, // helper for frontend
        ];
    }
}
