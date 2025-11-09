<?php

namespace App\Services;

class FileMetadataService
{
    /**
     * Get file type based on extension
     */
    public function getFileType(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($ext) {
            'txt' => 'text',
            'pdf' => 'pdf',
            'doc', 'docx' => 'word',
            'xls', 'xlsx' => 'excel',
            'jpg', 'jpeg', 'png', 'gif', 'bmp' => 'image',
            'mp4', 'avi', 'mov', 'mkv' => 'video',
            'mp3', 'wav', 'flac' => 'audio',
            'zip', 'rar', '7z' => 'archive',
            default => 'file',
        };
    }

    /**
     * Get detailed file metadata
     */
    public function getFileMetadata(string $filePath): array
    {
        $stat = @stat($filePath);
        if ($stat === false) {
            return [];
        }

        $metadata = [
            'size' => filesize($filePath),
            'modified' => filemtime($filePath),
            'created' => $stat['ctime'],
            'accessed' => $stat['atime'],
            'readable' => is_readable($filePath),
            'writable' => is_writable($filePath),
        ];

        if (!is_dir($filePath)) {
            $mode = $stat['mode'];
            if (PHP_OS_FAMILY === 'Windows') {
                $metadata['readonly'] = !$metadata['writable'];
            } else {
                $metadata['permissions'] = substr(sprintf('%o', $mode), -4);
            }
        }

        return $metadata;
    }
}
