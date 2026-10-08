<?php
declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\Content;
use App\Domain\Repository\ContentRepository;

final class ListContent
{
    private const MAX_PER_PAGE = 50;

    public function __construct(private readonly ContentRepository $contents)
    {
    }

    /** @return Content[] */
    public function execute(?string $query, ?string $genre, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = min(self::MAX_PER_PAGE, max(1, $perPage));
        $query = $query !== null && trim($query) !== '' ? trim($query) : null;
        $genre = $genre !== null && trim($genre) !== '' ? trim($genre) : null;

        return $this->contents->searchPublished($query, $genre, $perPage, ($page - 1) * $perPage);
    }
}
