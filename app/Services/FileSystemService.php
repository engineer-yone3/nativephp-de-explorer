<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Native\Desktop\Facades\Shell;

class FileSystemService
{
    public function __construct(
        private FileMetadataService $metadataService
    ) {}

    /**
     * Get all items (files and directories) in a directory
     */
    public function getDirectoryItems(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $items = [];

        try {
            $files = @scandir($path);
            if ($files === false) {
                Log::warning('Failed to scan directory', ['path' => $path]);
                return [];
            }

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $filePath = $path . DIRECTORY_SEPARATOR . $file;
                if (!is_readable($filePath)) {
                    continue;
                }

                $isDir = is_dir($filePath);
                $items[] = [
                    'name' => $file,
                    'path' => $filePath,
                    'isDirectory' => $isDir,
                    'type' => $isDir ? 'folder' : $this->metadataService->getFileType($file),
                    'size' => $isDir ? null : @filesize($filePath),
                    'modified' => @filemtime($filePath),
                    'metadata' => $this->metadataService->getFileMetadata($filePath),
                ];
            }

            // Sort: directories first, then alphabetically
            usort($items, function ($a, $b) {
                if ($a['isDirectory'] !== $b['isDirectory']) {
                    return $b['isDirectory'] - $a['isDirectory'];
                }
                return strcasecmp($a['name'], $b['name']);
            });

        } catch (\Exception $e) {
            Log::error('Error reading directory', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            return [];
        }

        return $items;
    }

    /**
     * Get directory tree recursively with depth limit and circular reference protection
     */
    public function getDirectoryTree(string $basePath, int $maxDepth = 3, int $currentDepth = 0, array &$visitedPaths = []): array
    {
        if ($currentDepth >= $maxDepth || !is_dir($basePath) || !is_readable($basePath)) {
            return [];
        }

        // Protect against circular references
        $realPath = realpath($basePath);
        if ($realPath === false) {
            return [];
        }

        if (isset($visitedPaths[$realPath])) {
            return [];
        }

        $visitedPaths[$realPath] = true;
        $children = [];

        try {
            $files = @scandir($basePath);
            if ($files === false) {
                return [];
            }

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $filePath = $basePath . DIRECTORY_SEPARATOR . $file;
                if (!is_readable($filePath) || !is_dir($filePath)) {
                    continue;
                }

                $children[] = [
                    'name' => $file,
                    'path' => $filePath,
                    'children' => $this->getDirectoryTree($filePath, $maxDepth, $currentDepth + 1, $visitedPaths),
                ];
            }

            // Sort alphabetically
            usort($children, function ($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });

        } catch (\Exception $e) {
            Log::warning('Error building directory tree', [
                'path' => $basePath,
                'error' => $e->getMessage(),
            ]);
            return [];
        }

        return $children;
    }

    /**
     * Open a file using the system's default application
     */
    public function openFile(string $filePath): array
    {
        // Validate file path
        if (!$filePath || !is_string($filePath)) {
            return [
                'success' => false,
                'message' => __('messages.file_path_not_specified'),
                'code' => 400,
            ];
        }

        // Normalize path for Windows
        if (PHP_OS_FAMILY === 'Windows') {
            $filePath = str_replace('/', '\\', $filePath);
        }

        // Check if file exists
        if (!@file_exists($filePath)) {
            return [
                'success' => false,
                'message' => __('messages.file_not_found'),
                'code' => 404,
            ];
        }

        // Ensure it's a file, not a directory
        $isDir = @is_dir($filePath);
        if ($isDir === null || $isDir === true) {
            return [
                'success' => false,
                'message' => __('messages.is_folder_not_file'),
                'code' => 400,
            ];
        }

        // Check read permissions
        if (!@is_readable($filePath)) {
            return [
                'success' => false,
                'message' => __('messages.file_read_permission'),
                'code' => 403,
            ];
        }

        // Try to open the file
        try {
            $error = Shell::openFile($filePath);

            if ($error) {
                Log::warning('File open warning', [
                    'filePath' => $filePath,
                    'error' => $error,
                ]);
                return [
                    'success' => false,
                    'message' => __('messages.error_opening_file') . ': ' . $error,
                    'code' => 500,
                ];
            }

            Log::debug('File opened successfully', [
                'filePath' => $filePath,
            ]);

            return [
                'success' => true,
                'message' => __('messages.file_opened_successfully'),
                'code' => 200,
            ];
        } catch (\Exception $e) {
            Log::error('File open error', [
                'error' => $e->getMessage(),
                'filePath' => $filePath,
            ]);
            return [
                'success' => false,
                'message' => __('messages.error_opening_file') . ': ' . $e->getMessage(),
                'code' => 500,
            ];
        }
    }

    /**
     * Validate and sanitize directory path
     */
    public function validatePath(string $path): ?string
    {
        if (!$path || !is_string($path)) {
            return null;
        }

        // Resolve to absolute path to prevent path traversal attacks
        $realPath = realpath($path);
        if ($realPath === false || !is_dir($realPath) || !is_readable($realPath)) {
            return null;
        }

        return $realPath;
    }
}
