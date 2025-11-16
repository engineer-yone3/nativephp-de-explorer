<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Native\Desktop\Facades\Shell;

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

        // ルートドライブ/マウントポイントを取得し、各ドライブのツリーを構築
        $rootDrives = $this->getRootDrives();
        foreach ($rootDrives as &$drive) {
            $drive['children'] = $this->getDirectoryTree($drive['path']);
        }
        unset($drive);

        return view('explorer.index', [
            'currentPath' => $userPath,
            'items' => $items,
            'quickAccessPaths' => $quickAccessPaths,
            'rootDrives' => $rootDrives,
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

    /**
     * 指定パスからツリー構造を取得
     */
    public function getDirectoryTree(string $basePath, int $maxDepth = 3, int $currentDepth = 0, array &$visitedPaths = []): array
    {
        if ($currentDepth >= $maxDepth || !is_dir($basePath) || !is_readable($basePath)) {
            return [];
        }

        // 実パスを取得して循環参照を防止
        $realPath = realpath($basePath);
        if ($realPath === false) {
            return [];
        }

        // すでに訪問済みの場合はスキップ（循環参照の防止）
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
                // . と .. をスキップ
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $filePath = $basePath . DIRECTORY_SEPARATOR . $file;

                // アクセス権限がない場合はスキップ
                if (!is_readable($filePath)) {
                    continue;
                }

                // ディレクトリのみを対象とする
                if (!is_dir($filePath)) {
                    continue;
                }

                $children[] = [
                    'name' => $file,
                    'path' => $filePath,
                    'children' => $this->getDirectoryTree($filePath, $maxDepth, $currentDepth + 1, $visitedPaths),
                ];
            }

            // アルファベット順でソート
            usort($children, function ($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });

        } catch (\Exception $e) {
            return [];
        }

        return $children;
    }

    /**
     * OSに応じてルートドライブ/マウントポイントを取得
     */
    private function getRootDrives(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return $this->getWindowsDrives();
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            return $this->getMacVolumes();
        } elseif (PHP_OS_FAMILY === 'Linux') {
            return $this->getLinuxMounts();
        }

        return [];
    }

    /**
     * Windows環境でのドライブを取得
     */
    private function getWindowsDrives(): array
    {
        $drives = [];

        // A-Z のドライブレターをチェック
        for ($letter = ord('A'); $letter <= ord('Z'); $letter++) {
            $driveLetter = chr($letter);
            $path = $driveLetter . ':' . DIRECTORY_SEPARATOR;

            if (is_dir($path) && is_readable($path)) {
                $drives[] = [
                    'name' => $driveLetter . ':',
                    'path' => $path,
                    'children' => [],
                ];
            }
        }

        return $drives;
    }

    /**
     * macOS環境でのボリュームを取得
     */
    private function getMacVolumes(): array
    {
        $volumes = [];

        // /Volumes ディレクトリからマウント済みボリュームを取得
        $volumesPath = '/Volumes';
        if (is_dir($volumesPath) && is_readable($volumesPath)) {
            $items = @scandir($volumesPath);

            if ($items !== false) {
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') {
                        continue;
                    }

                    $fullPath = $volumesPath . DIRECTORY_SEPARATOR . $item;

                    if (is_dir($fullPath) && is_readable($fullPath)) {
                        $volumes[] = [
                            'name' => $item,
                            'path' => $fullPath,
                            'children' => [],
                        ];
                    }
                }
            }
        }

        // システム用ボリューム（通常は macOS システム自体）
        $systemPath = '/';
        if (is_dir($systemPath) && is_readable($systemPath)) {
            array_unshift($volumes, [
                'name' => 'Macintosh HD',
                'path' => $systemPath,
                'children' => [],
            ]);
        }

        return $volumes;
    }

    /**
     * Linux環境でのマウント情報を取得
     */
    private function getLinuxMounts(): array
    {
        $mounts = [];

        // /etc/mtab または /proc/mounts から マウント情報を読取
        $mountFile = file_exists('/etc/mtab') ? '/etc/mtab' : '/proc/mounts';

        if (file_exists($mountFile)) {
            $lines = file($mountFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            if ($lines !== false) {
                foreach ($lines as $line) {
                    $parts = preg_split('/\s+/', $line);

                    if (count($parts) >= 2) {
                        $devicePath = $parts[0];
                        $mountPath = $parts[1];

                        // 実際にマウントされており読取可能か確認
                        if (is_dir($mountPath) && is_readable($mountPath)) {
                            // 標準的なファイルシステムのみを対象とする
                            if ($this->isRelevantLinuxMount($devicePath, $mountPath)) {
                                $name = basename($mountPath) ?: $mountPath;
                                // ルートは特別な名前をつける
                                if ($mountPath === '/') {
                                    $name = 'System';
                                }

                                $mounts[] = [
                                    'name' => $name,
                                    'path' => $mountPath,
                                    'children' => [],
                                ];
                            }
                        }
                    }
                }
            }
        }

        // ルートが含まれていなければ追加
        if (empty($mounts) || !array_key_exists(0, $mounts) || $mounts[0]['path'] !== '/') {
            array_unshift($mounts, [
                'name' => 'System',
                'path' => '/',
                'children' => [],
            ]);
        }

        return $mounts;
    }

    /**
     * Linux環境で表示対象のマウントか判定
     */
    private function isRelevantLinuxMount(string $device, string $mountPath): bool
    {
        // 除外するパターン
        $excludePatterns = [
            '/sys',
            '/proc',
            '/dev/shm',
            '/run',
            '/boot/efi',
            '/snap/',
        ];

        foreach ($excludePatterns as $pattern) {
            if (strpos($mountPath, $pattern) === 0) {
                return false;
            }
        }

        // 除外するデバイスパターン
        $excludeDevices = ['tmpfs', 'devtmpfs', 'sysfs', 'proc', 'cgroup'];

        foreach ($excludeDevices as $excludeDevice) {
            if (strpos($device, $excludeDevice) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * ファイルをOSのデフォルトアプリケーションで開く
     */
    public function openFile(): JsonResponse
    {
        try {
            $filePath = request()->input('path');

            // パスの入力検証
            if (!$filePath || !is_string($filePath)) {
                return response()->json(['success' => false, 'message' => 'ファイルパスが指定されていません'], 400);
            }

            // Windows環境では、フォワードスラッシュをバックスラッシュに統一
            if (PHP_OS_FAMILY === 'Windows') {
                $filePath = str_replace('/', '\\', $filePath);
            }

            // ファイルの存在確認
            if (!@file_exists($filePath)) {
                return response()->json(['success' => false, 'message' => 'ファイルが見つかりません'], 404);
            }

            // ディレクトリでないことを確認
            $isDir = @is_dir($filePath);
            if ($isDir === null || $isDir === true) {
                return response()->json(['success' => false, 'message' => 'フォルダではなくファイルを指定してください'], 400);
            }

            // ファイルが読取可能か確認
            if (!@is_readable($filePath)) {
                return response()->json(['success' => false, 'message' => 'ファイルの読み込み権限がありません'], 403);
            }

            // NativePHPのShell::openFile()を使用してOS側でファイルを開く
            $error = Shell::openFile($filePath);

            if ($error) {
                \Log::warning('ファイルオープン時の警告', [
                    'filePath' => $filePath,
                    'error' => $error,
                ]);
                return response()->json(['success' => false, 'message' => 'ファイルを開く際にエラーが発生しました: ' . $error], 500);
            }

            \Log::debug('ファイルを開きました', [
                'filePath' => $filePath,
            ]);

            return response()->json(['success' => true, 'message' => 'ファイルを開いています']);
        } catch (\Exception $e) {
            // エラーログに記録
            \Log::error('ファイルオープンエラー', [
                'error' => $e->getMessage(),
                'filePath' => $filePath ?? 'undefined',
            ]);
            return response()->json(['success' => false, 'message' => 'ファイルを開く際にエラーが発生しました: ' . $e->getMessage()], 500);
        }
    }
}

