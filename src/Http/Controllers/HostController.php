<?php

namespace ArchiElite\LogViewer\Http\Controllers;

use ArchiElite\LogViewer\Facades\LogViewer;
use ArchiElite\LogViewer\Http\Resources\LogViewerHostResource;
use Botble\Base\Http\Controllers\BaseController;
use Illuminate\Http\Resources\Json\ResourceCollection;

class HostController extends BaseController
{
    public function index(): ResourceCollection
    {
        return LogViewerHostResource::collection(
            LogViewer::getHosts()
        );
    }
}
