<?php

namespace App\Domain\Cart\Entities;

use InvalidArgumentException;

final class CartItem
{
    private int $productId;
    private int $quantity;

    private function __construct(int $productId, int $quantity)
    {
        $this->productId = $productId;
        $this->setQuantity($quantity);
    }

    public static function make(int $productId, int $quantity = 1): self
    {
        return new self($productId, $quantity);
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function increase(int $by = 1): void
    {
        if ($by < 1) {
            throw new InvalidArgumentException('Quantity increment must be at least 1.');
        }

        $this->quantity += $by;
    }

    private function setQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        $this->quantity = $quantity;
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
        ];
    }
}

