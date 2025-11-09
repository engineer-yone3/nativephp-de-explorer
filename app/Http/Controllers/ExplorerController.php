<?php

namespace App\Http\Controllers;

use App\Services\FileSystemService;
use App\Services\PathResolverService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ExplorerController extends Controller
{
    public function __construct(
        private FileSystemService $fileSystemService,
        private PathResolverService $pathResolverService
    ) {}

    public function index(): View
    {
        logger()->debug('Request Params: ', [request()->query(null)]);

        // Handle language parameter
        $requestLanguage = request()->query('language', null);
        if ($requestLanguage && in_array($requestLanguage, ['en', 'ja'])) {
            app()->setLocale($requestLanguage);
            session(['locale' => $requestLanguage]);
        }

        // Handle view mode parameter
        $requestMode = request()->query('mode', null);
        $viewMode = (in_array($requestMode, ['grid', 'list', 'detail'])) ? $requestMode : 'grid';

        $defaultUserPath = $this->pathResolverService->getUserProfilePath();
        $requestPath = request()->query('path', null);

        // Validate and sanitize the requested path
        if ($requestPath && is_string($requestPath)) {
            $validatedPath = $this->fileSystemService->validatePath($requestPath);
            $userPath = $validatedPath ?? $defaultUserPath;
        } else {
            $userPath = $defaultUserPath;
        }

        $items = $this->fileSystemService->getDirectoryItems($userPath);
        $quickAccessPaths = $this->pathResolverService->getQuickAccessPaths($defaultUserPath);

        $rootDrives = $this->pathResolverService->getRootDrives();
        foreach ($rootDrives as &$drive) {
            $drive['children'] = $this->fileSystemService->getDirectoryTree($drive['path']);
        }
        unset($drive);

        return view('explorer.index', [
            'currentPath' => $userPath,
            'items' => $items,
            'quickAccessPaths' => $quickAccessPaths,
            'rootDrives' => $rootDrives,
            'viewMode' => $viewMode,
        ]);
    }

    public function openFile(): JsonResponse
    {
        $filePath = request()->input('path');
        $result = $this->fileSystemService->openFile($filePath);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $result['code']);
    }
}
