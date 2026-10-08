<?php
declare(strict_types=1);

namespace App\Infrastructure\Media;

use App\Application\Port\MediaStorage;

final class LocalMediaStorage implements MediaStorage
{
    public function __construct(private readonly string $basePath)
    {
    }

    public function saveSource(int $contentId, string $tmpFile, string $extension): string
    {
        $relative = "sources/{$contentId}/source.{$extension}";
        $target = $this->basePath . '/' . $relative;
        $this->ensureDir(dirname($target));

        if (!@rename($tmpFile, $target) && !(copy($tmpFile, $target) && unlink($tmpFile))) {
            throw new \RuntimeException('Не удалось сохранить исходный файл');
        }

        return $relative;
    }

    public function sourceAbsolutePath(string $relativePath): string
    {
        return $this->basePath . '/' . $relativePath;
    }

    public function hlsDirectory(int $contentId): string
    {
        $dir = $this->basePath . '/' . $this->hlsRelativeDirectory($contentId);
        $this->ensureDir($dir);

        return $dir;
    }

    public function hlsRelativeDirectory(int $contentId): string
    {
        return "hls/{$contentId}";
    }

    public function hlsFile(int $contentId, string $fileName): ?string
    {
        $path = $this->basePath . '/' . $this->hlsRelativeDirectory($contentId) . '/' . basename($fileName);

        return is_file($path) ? $path : null;
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException("Не удалось создать каталог {$dir}");
        }
    }
}
