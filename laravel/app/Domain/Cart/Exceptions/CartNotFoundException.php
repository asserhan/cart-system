<?php

namespace App\Domain\Cart\Exceptions;

use RuntimeException;

final class CartNotFoundException extends RuntimeException
{
    public static function withId(int $cartId): self
    {
        return new self(sprintf('Cart with id %d was not found.', $cartId));
    }
}