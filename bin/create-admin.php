<?php
declare(strict_types=1);

use App\Application\Port\PasswordHasher;
use App\Domain\Entity\User;
use App\Domain\Repository\UserRepository;

require __DIR__ . '/../vendor/autoload.php';

[$script, $email, $password] = $argv + [null, null, null];
if (!$email || !$password || strlen($password) < 8) {
    fwrite(STDERR, "Использование: php bin/create-admin.php <email> <пароль не короче 8 символов>\n");
    exit(1);
}

$container = (require __DIR__ . '/../config/container.php')();
$user = $container->get(UserRepository::class)->save(
    new User(null, mb_strtolower($email), $container->get(PasswordHasher::class)->hash($password), User::ROLE_ADMIN)
);
echo "Администратор создан, id={$user->id}\n";
