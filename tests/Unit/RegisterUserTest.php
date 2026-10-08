<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Application\UseCase\RegisterUser;
use App\Domain\Exception\ConflictException;
use App\Domain\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Tests\Fake\InMemoryUserRepository;
use Tests\Fake\PlainPasswordHasher;

final class RegisterUserTest extends TestCase
{
    private function useCase(): RegisterUser
    {
        return new RegisterUser(new InMemoryUserRepository(), new PlainPasswordHasher());
    }

    public function testRegistersViewerWithNormalizedEmail(): void
    {
        $user = $this->useCase()->execute('  Maxim@Example.COM ', 'password123');

        $this->assertSame('maxim@example.com', $user->email);
        $this->assertFalse($user->isAdmin());
        $this->assertNotSame('password123', $user->passwordHash);
    }

    public function testRejectsShortPassword(): void
    {
        $this->expectException(ValidationException::class);
        $this->useCase()->execute('a@b.ru', 'short');
    }

    public function testRejectsInvalidEmail(): void
    {
        $this->expectException(ValidationException::class);
        $this->useCase()->execute('not-an-email', 'password123');
    }

    public function testRejectsDuplicateEmail(): void
    {
        $uc = $this->useCase();
        $uc->execute('a@b.ru', 'password123');

        $this->expectException(ConflictException::class);
        $uc->execute('A@B.ru', 'password123');
    }
}
