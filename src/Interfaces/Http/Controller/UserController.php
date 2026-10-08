<?php
declare(strict_types=1);

namespace App\Interfaces\Http\Controller;

use App\Application\UseCase\GetWatchHistory;
use App\Application\UseCase\SaveProgress;
use App\Application\UseCase\SubscribeToPlan;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UserController extends BaseController
{
    public function __construct(
        private readonly SubscribeToPlan $subscribe,
        private readonly SaveProgress $saveProgress,
        private readonly GetWatchHistory $history,
    ) {
    }

    public function subscribe(ServerRequestInterface $req, ResponseInterface $res): ResponseInterface
    {
        $s = $this->subscribe->execute((int) $req->getAttribute('userId'), (int) ($this->body($req)['plan_id'] ?? 0));

        return $this->json($res, [
            'id' => $s->id,
            'plan_id' => $s->planId,
            'starts_at' => $s->startsAt->format(DATE_ATOM),
            'ends_at' => $s->endsAt->format(DATE_ATOM),
        ], 201);
    }

    /** @param array{id:string} $args */
    public function saveProgress(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        $this->saveProgress->execute(
            (int) $req->getAttribute('userId'),
            (int) $args['id'],
            (int) ($this->body($req)['position_seconds'] ?? -1),
        );

        return $res->withStatus(204);
    }

    public function history(ServerRequestInterface $req, ResponseInterface $res): ResponseInterface
    {
        $items = $this->history->execute((int) $req->getAttribute('userId'));

        return $this->json($res, ['items' => array_map(static fn ($p) => [
            'content_id' => $p->contentId,
            'position_seconds' => $p->positionSeconds,
            'updated_at' => $p->updatedAt->format(DATE_ATOM),
        ], $items)]);
    }
}
