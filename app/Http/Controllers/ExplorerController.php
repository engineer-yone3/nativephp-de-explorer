<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class ExplorerController extends Controller
{
    /**
     * エクスプローラー画面を表示
     */
    public function index(): View
    {
        // ユーザープロファイルパスを取得（デフォルト値）
        $defaultUserPath = $this->getUserProfilePath();
        
        // URLのクエリパラメータから指定パスを取得（あればそれを使用）
        $requestPath = request()->query('path', null);
        
        if ($requestPath && is_string($requestPath) && is_dir($requestPath)) {
            $userPath = $requestPath;
        } else {
            // 無効なパスならホームに戻す
            $userPath = $defaultUserPath;
        }
        
        // 指定パスのファイル/フォルダを取得
        $items = $this->getDirectoryItems($userPath);
        
        // クイックアクセスのパスを取得
        $quickAccessPaths = $this->getQuickAccessPaths($defaultUserPath);
        
        return view('explorer.index', [
            'currentPath' => $userPath,
            'items' => $items,
            'quickAccessPaths' => $quickAccessPaths,
        ]);
    }

    /**
     * ユーザープロファイルパスを取得
     */
    private function getUserProfilePath(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows環境でのユーザープロファイルパス
            $userProfile = getenv('USERPROFILE');
            
            if ($userProfile === false) {
                throw new \RuntimeException('Unable to determine user profile path on Windows. USERPROFILE environment variable not found.');
            }
            
            return $userProfile;
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            // macOS環境
            $home = getenv('HOME');
            
            if ($home === false) {
                throw new \RuntimeException('Unable to determine home directory on macOS. HOME environment variable not found.');
            }
            
            return $home;
        } elseif (PHP_OS_FAMILY === 'Linux') {
            // Linux環境
            $home = getenv('HOME');
            
            if ($home === false) {
                throw new \RuntimeException('Unable to determine home directory on Linux. HOME environment variable not found.');
            }
            
            return $home;
        } else {
            throw new \RuntimeException('Unsupported operating system: ' . PHP_OS_FAMILY);
        }
    }

    /**
     * ディレクトリ内のアイテム一覧を取得
     */
    private function getDirectoryItems(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $items = [];
        
        try {
            $files = @scandir($path);
            
            if ($files === false) {
                return [];
            }

            foreach ($files as $file) {
                // . と .. をスキップ
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $filePath = $path . DIRECTORY_SEPARATOR . $file;
                
                // アクセス権限がない場合はスキップ
                if (!is_readable($filePath)) {
                    continue;
                }

                $isDir = is_dir($filePath);
                
                $items[] = [
                    'name' => $file,
                    'path' => $filePath,
                    'isDirectory' => $isDir,
                    'type' => $isDir ? 'folder' : $this->getFileType($file),
                    'size' => $isDir ? null : filesize($filePath),
                    'modified' => filemtime($filePath),
                    'metadata' => $this->getFileMetadata($filePath),
                ];
            }

            // ディレクトリを上に、その次にファイル名でソート
            usort($items, function ($a, $b) {
                if ($a['isDirectory'] !== $b['isDirectory']) {
                    return $b['isDirectory'] - $a['isDirectory'];
                }
                return strcasecmp($a['name'], $b['name']);
            });

        } catch (\Exception $e) {
            return [];
        }

        return $items;
    }

    /**
     * ファイルタイプを取得
     */
    private function getFileType(string $filename): string
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
     * クイックアクセスのパスを取得
     */
    private function getQuickAccessPaths(string $userPath): array
    {
        $paths = [
            'home' => [
                'label' => '🏠 ホーム',
                'path' => $userPath,
            ],
        ];

        if (PHP_OS_FAMILY === 'Windows') {
            $paths['desktop'] = [
                'label' => '🖥️ デスクトップ',
                'path' => $userPath . DIRECTORY_SEPARATOR . 'Desktop',
            ];
            $paths['documents'] = [
                'label' => '📄 ドキュメント',
                'path' => $userPath . DIRECTORY_SEPARATOR . 'Documents',
            ];
            $paths['downloads'] = [
                'label' => '⬇️ ダウンロード',
                'path' => $userPath . DIRECTORY_SEPARATOR . 'Downloads',
            ];
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            // macOS
            $paths['desktop'] = [
                'label' => '🖥️ デスクトップ',
                'path' => $userPath . DIRECTORY_SEPARATOR . 'Desktop',
            ];
            $paths['documents'] = [
                'label' => '📄 書類',
                'path' => $userPath . DIRECTORY_SEPARATOR . 'Documents',
            ];
            $paths['downloads'] = [
                'label' => '⬇️ ダウンロード',
                'path' => $userPath . DIRECTORY_SEPARATOR . 'Downloads',
            ];
        } elseif (PHP_OS_FAMILY === 'Linux') {
            // Linux
            $paths['desktop'] = [
                'label' => '🖥️ デスクトップ',
                'path' => $userPath . DIRECTORY_SEPARATOR . 'Desktop',
            ];
            $paths['documents'] = [
                'label' => '📄 書類',
                'path' => $userPath . DIRECTORY_SEPARATOR . 'Documents',
            ];
            $paths['downloads'] = [
                'label' => '⬇️ ダウンロード',
                'path' => $userPath . DIRECTORY_SEPARATOR . 'Downloads',
            ];
        }

        return $paths;
    }

    /**
     * ファイル/フォルダの詳細メタデータを取得
     */
    private function getFileMetadata(string $filePath): array
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

        // ファイルとディレクトリを区別
        if (!is_dir($filePath)) {
            // ファイル属性（読み取り専用など）
            $mode = $stat['mode'];
            // Windows環境では mode から読み取り専用属性を判定
            if (PHP_OS_FAMILY === 'Windows') {
                // Windowsでは fileperms() で読み取り専用属性を判定
                $metadata['readonly'] = !$metadata['writable'];
            } else {
                // Unix系では octal permissions を取得
                $metadata['permissions'] = substr(sprintf('%o', $mode), -4);
            }
        }

        return $metadata;
    }
}
