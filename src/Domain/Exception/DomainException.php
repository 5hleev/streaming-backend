<?php
declare(strict_types=1);

namespace App\Domain\Exception;

/** Базовое исключение предметной области. httpStatus использует только внешний слой для отображения ошибки. */
abstract class DomainException extends \RuntimeException
{
    public function __construct(string $message, private readonly int $httpStatus)
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }
}
