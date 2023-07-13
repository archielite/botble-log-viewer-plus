<?php

namespace ArchiElite\LogViewer\Http\Controllers;

use ArchiElite\LogViewer\Facades\LogViewer;
use ArchiElite\LogViewer\Http\Resources\LogFileResource;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileController extends BaseController
{
    public function index(Request $request): ResourceCollection
    {
        JsonResource::withoutWrapping();

        $files = LogViewer::getFiles();

        if ($request->query('direction', 'desc') === 'asc') {
            $files = $files->sortByEarliestFirst();
        } else {
            $files = $files->sortByLatestFirst();
        }

        return LogFileResource::collection($files);
    }

    public function download(string $fileIdentifier): BinaryFileResponse
    {
        $file = LogViewer::getFile($fileIdentifier);

        abort_if(is_null($file), 404);

        return $file->download();
    }

    public function clearCache(string $fileIdentifier, BaseHttpResponse $response): BaseHttpResponse
    {
        $file = LogViewer::getFile($fileIdentifier);

        abort_if(is_null($file), 404);

        $file->clearCache();

        return $response;
    }

    public function clearCacheAll(BaseHttpResponse $response): BaseHttpResponse
    {
        LogViewer::getFiles()->each->clearCache();

        return $response;
    }

    public function delete(string $fileIdentifier, BaseHttpResponse $response): BaseHttpResponse
    {
        $file = LogViewer::getFile($fileIdentifier);

        if (is_null($file)) {
            return $response;
        }

        $file->delete();

        return $response;
    }

    public function deleteMultipleFiles(Request $request, BaseHttpResponse $response): BaseHttpResponse
    {
        $selectedFilesArray = (array)$request->input('files', []);

        foreach ($selectedFilesArray as $fileIdentifier) {
            $file = LogViewer::getFile($fileIdentifier);

            $file->delete();
        }

        return $response;
    }
}
