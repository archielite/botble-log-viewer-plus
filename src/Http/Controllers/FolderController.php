<?php

namespace ArchiElite\LogViewer\Http\Controllers;

use ArchiElite\LogViewer\Facades\LogViewer;
use ArchiElite\LogViewer\Http\Resources\LogFolderResource;
use ArchiElite\LogViewer\LogFile;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FolderController extends BaseController
{
    public function index(Request $request): ResourceCollection
    {
        JsonResource::withoutWrapping();

        $folders = LogViewer::getFilesGroupedByFolder();

        if ($request->query('direction', 'desc') === 'asc') {
            $folders = $folders->sortByEarliestFirstIncludingFiles();
        } else {
            $folders = $folders->sortByLatestFirstIncludingFiles();
        }

        return LogFolderResource::collection($folders->values());
    }

    public function download(string $folderIdentifier): BinaryFileResponse
    {
        $folder = LogViewer::getFolder($folderIdentifier);

        abort_if(is_null($folder), 404);

        return $folder->download();
    }

    public function clearCache(string $folderIdentifier, BaseHttpResponse $response): BaseHttpResponse
    {
        $folder = LogViewer::getFolder($folderIdentifier);

        abort_if(is_null($folder), 404);

        $folder->files()->each(fn (LogFile $file) => $file->clearCache());

        return $response;
    }

    public function delete(string $folderIdentifier, BaseHttpResponse $response): BaseHttpResponse
    {
        $folder = LogViewer::getFolder($folderIdentifier);

        if (is_null($folder)) {
            return $response;
        }

        $folder->files()->each(fn (LogFile $file) => $file->delete());

        return $response;
    }
}
