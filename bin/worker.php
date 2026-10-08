<?php
declare(strict_types=1);

use App\Application\UseCase\ProcessNextTranscodeJob;

require __DIR__ . '/../vendor/autoload.php';

$container = (require __DIR__ . '/../config/container.php')();
$useCase = $container->get(ProcessNextTranscodeJob::class);

fwrite(STDOUT, "Воркер транскодирования запущен\n");
while (true) {
    try {
        if (!$useCase->execute()) {
            sleep(2); // очередь пуста
        }
    } catch (Throwable $e) {
        fwrite(STDERR, $e . "\n");
        sleep(5);
    }
}
