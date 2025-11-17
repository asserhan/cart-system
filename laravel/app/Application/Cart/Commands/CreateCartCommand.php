<?php

namespace App\Application\Cart\Commands;

final class CreateCartCommand
{
    public function __construct(
        public readonly ?int $userId,
        public readonly string $email,
    ) {
    }
}

