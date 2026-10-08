<?php
declare(strict_types=1);

namespace Tests\Fake;

use App\Domain\Entity\Content;
use App\Domain\Repository\ContentRepository;

final class InMemoryContentRepository implements ContentRepository
{
    /** @var array<int,Content> */
    public array $items = [];

    public function save(Content $content): Content
    {
        $content = $content->id === null ? $content->withId(count($this->items) + 1) : $content;
        $this->items[$content->id] = $content;

        return $content;
    }

    public function findById(int $id): ?Content
    {
        return $this->items[$id] ?? null;
    }

    public function searchPublished(?string $query, ?string $genre, int $limit, int $offset): array
    {
        $found = array_filter($this->items, static fn (Content $c) => $c->isPublished()
            && ($query === null || stripos($c->title, $query) !== false)
            && ($genre === null || strcasecmp($c->genre, $genre) === 0));

        return array_slice(array_values($found), $offset, $limit);
    }
}
