<?php

namespace App\Application\Cart\Commands;

use InvalidArgumentException;

final class AddCartItemCommand
{
    public function __construct(
        public readonly int $cartId,
        public readonly int $productId,
        public readonly int $quantity = 1,
    ) {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }
    }
}

