<?php
declare(strict_types=1);

use App\Application\Port\Clock;
use App\Application\Port\MediaStorage;
use App\Application\Port\PasswordHasher;
use App\Application\Port\TokenService;
use App\Application\Port\Transcoder;
use App\Application\Port\UrlSigner;
use App\Application\UseCase\RequestStream;
use App\Domain\Repository\ContentRepository;
use App\Domain\Repository\PlanRepository;
use App\Domain\Repository\SubscriptionRepository;
use App\Domain\Repository\TranscodeJobRepository;
use App\Domain\Repository\UserRepository;
use App\Domain\Repository\WatchProgressRepository;
use App\Infrastructure\Media\FfmpegTranscoder;
use App\Infrastructure\Media\LocalMediaStorage;
use App\Infrastructure\Persistence\ConnectionFactory;
use App\Interfaces\Persistence\PdoContentRepository;
use App\Interfaces\Persistence\PdoPlanRepository;
use App\Interfaces\Persistence\PdoSubscriptionRepository;
use App\Interfaces\Persistence\PdoTranscodeJobRepository;
use App\Interfaces\Persistence\PdoUserRepository;
use App\Interfaces\Persistence\PdoWatchProgressRepository;
use App\Infrastructure\Security\BcryptPasswordHasher;
use App\Infrastructure\Security\HmacUrlSigner;
use App\Infrastructure\Security\JwtTokenService;
use App\Infrastructure\Security\SystemClock;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Slim\Psr7\Factory\StreamFactory;

use function DI\autowire;

/** Composition root: единственное место, где интерфейсы связываются с реализациями. */
return static function (): ContainerInterface {
    $env = static fn (string $key, ?string $default = null): string =>
        getenv($key) !== false ? (string) getenv($key) : ($default ?? throw new RuntimeException("Не задана переменная окружения {$key}"));

    $builder = new ContainerBuilder();
    $builder->addDefinitions([
        PDO::class => static fn () => ConnectionFactory::create($env('DB_DSN'), $env('DB_USER'), $env('DB_PASS')),

        StreamFactoryInterface::class => autowire(StreamFactory::class),

        Clock::class => autowire(SystemClock::class),
        PasswordHasher::class => autowire(BcryptPasswordHasher::class),
        TokenService::class => static fn (ContainerInterface $c) => new JwtTokenService($env('JWT_SECRET'), $c->get(Clock::class)),
        UrlSigner::class => static fn (ContainerInterface $c) => new HmacUrlSigner($env('URL_SIGN_SECRET'), $c->get(Clock::class)),
        MediaStorage::class => static fn () => new LocalMediaStorage($env('STORAGE_PATH')),
        Transcoder::class => static fn () => new FfmpegTranscoder(),

        UserRepository::class => autowire(PdoUserRepository::class),
        ContentRepository::class => autowire(PdoContentRepository::class),
        PlanRepository::class => autowire(PdoPlanRepository::class),
        SubscriptionRepository::class => autowire(PdoSubscriptionRepository::class),
        TranscodeJobRepository::class => autowire(PdoTranscodeJobRepository::class),
        WatchProgressRepository::class => autowire(PdoWatchProgressRepository::class),

        RequestStream::class => static fn (ContainerInterface $c) => new RequestStream(
            $c->get(ContentRepository::class),
            $c->get(SubscriptionRepository::class),
            $c->get(UrlSigner::class),
            $c->get(Clock::class),
            (int) $env('STREAM_URL_TTL', '300'),
        ),
    ]);

    return $builder->build();
};
