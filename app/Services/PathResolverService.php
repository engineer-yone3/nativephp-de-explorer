<?php

namespace App\Services;

class PathResolverService
{
    /**
     * Get user's home/profile path based on OS
     */
    public function getUserProfilePath(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $userProfile = getenv('USERPROFILE');
            if ($userProfile === false) {
                throw new \RuntimeException('Unable to determine user profile path on Windows. USERPROFILE environment variable not found.');
            }
            return $userProfile;
        } elseif (PHP_OS_FAMILY === 'Darwin' || PHP_OS_FAMILY === 'Linux') {
            $home = getenv('HOME');
            if ($home === false) {
                throw new \RuntimeException('Unable to determine home directory. HOME environment variable not found.');
            }
            return $home;
        } else {
            throw new \RuntimeException('Unsupported operating system: ' . PHP_OS_FAMILY);
        }
    }

    /**
     * Get quick access paths for the sidebar
     */
    public function getQuickAccessPaths(string $userPath): array
    {
        $paths = [
            'home' => [
                'label' => __('messages.home'),
                'path' => $userPath,
            ],
        ];

        $commonPaths = [
            'desktop' => 'Desktop',
            'documents' => 'Documents',
            'downloads' => 'Downloads',
        ];

        foreach ($commonPaths as $key => $dirName) {
            $paths[$key] = [
                'label' => __('messages.' . $key),
                'path' => $userPath . DIRECTORY_SEPARATOR . $dirName,
            ];
        }

        return $paths;
    }

    /**
     * Get root drives based on OS
     */
    public function getRootDrives(): array
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
     * Get available Windows drives
     */
    private function getWindowsDrives(): array
    {
        $drives = [];

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
     * Get macOS volumes
     */
    private function getMacVolumes(): array
    {
        $volumes = [];

        // Add system root first
        $systemPath = '/';
        if (is_dir($systemPath) && is_readable($systemPath)) {
            // Try to get actual volume name
            $volumeName = $this->getMacSystemVolumeName();
            $volumes[] = [
                'name' => $volumeName,
                'path' => $systemPath,
                'children' => [],
            ];
        }

        // Add other volumes
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
                        // Skip if it's the same as the system root
                        $volumeRealPath = realpath($fullPath);
                        $systemRealPath = realpath($systemPath);

                        if ($volumeRealPath !== $systemRealPath) {
                            $volumes[] = [
                                'name' => $item,
                                'path' => $fullPath,
                                'children' => [],
                            ];
                        }
                    }
                }
            }
        }

        return $volumes;
    }

    /**
     * Get macOS system volume name
     */
    private function getMacSystemVolumeName(): string
    {
        // Try to get the actual volume name from diskutil
        $output = @shell_exec('diskutil info / 2>/dev/null | grep "Volume Name" | awk -F: \'{print $2}\' | xargs');
        if ($output && trim($output) !== '') {
            return trim($output);
        }

        // Fallback to default name
        return 'Macintosh HD';
    }

    /**
     * Get Linux mount points
     */
    private function getLinuxMounts(): array
    {
        $mounts = [];

        $mountFile = file_exists('/etc/mtab') ? '/etc/mtab' : '/proc/mounts';

        if (file_exists($mountFile)) {
            $lines = file($mountFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            if ($lines !== false) {
                foreach ($lines as $line) {
                    $parts = preg_split('/\s+/', $line);

                    if (count($parts) >= 2) {
                        $devicePath = $parts[0];
                        $mountPath = $parts[1];

                        if (is_dir($mountPath) && is_readable($mountPath)) {
                            if ($this->isRelevantLinuxMount($devicePath, $mountPath)) {
                                $name = basename($mountPath) ?: $mountPath;
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

        // Ensure root is present
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
     * Check if a Linux mount point is relevant for display
     */
    private function isRelevantLinuxMount(string $device, string $mountPath): bool
    {
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

        $excludeDevices = ['tmpfs', 'devtmpfs', 'sysfs', 'proc', 'cgroup'];

        foreach ($excludeDevices as $excludeDevice) {
            if (strpos($device, $excludeDevice) !== false) {
                return false;
            }
        }

        return true;
    }
}
