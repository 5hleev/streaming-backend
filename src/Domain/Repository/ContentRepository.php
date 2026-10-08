<?php
declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Content;

interface ContentRepository
{
    public function save(Content $content): Content;
    public function findById(int $id): ?Content;

    /** @return Content[] только опубликованный контент */
    public function searchPublished(?string $query, ?string $genre, int $limit, int $offset): array;
}
