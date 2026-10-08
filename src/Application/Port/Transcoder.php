<?php
declare(strict_types=1);

namespace App\Application\Port;

interface Transcoder
{
    /**
     * Преобразует исходный файл в HLS (плейлист master.m3u8 + сегменты) в каталоге $outputDir.
     * @throws \RuntimeException при ошибке транскодирования
     */
    public function toHls(string $sourceFile, string $outputDir): void;
}
