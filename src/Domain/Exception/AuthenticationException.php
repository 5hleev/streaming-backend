<?php
declare(strict_types=1);

namespace App\Domain\Exception;

final class AuthenticationException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 401);
    }
}
