<?php
declare(strict_types=1);

namespace App\Application\Port;

interface MediaStorage
{
    /** Сохраняет загруженный исходник и возвращает относительный путь. */
    public function saveSource(int $contentId, string $tmpFile, string $extension): string;

    /** Абсолютный путь к исходнику по относительному. */
    public function sourceAbsolutePath(string $relativePath): string;

    /** Абсолютный путь к каталогу HLS-выдачи контента (создаёт его при необходимости). */
    public function hlsDirectory(int $contentId): string;

    /** Относительный путь каталога HLS. */
    public function hlsRelativeDirectory(int $contentId): string;

    /** Абсолютный путь к файлу HLS-выдачи или null, если файла нет. Имя файла должно быть уже провалидировано. */
    public function hlsFile(int $contentId, string $fileName): ?string;
}
