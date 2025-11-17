<?php

namespace App\Domain\Cart\Repositories;

use App\Domain\Cart\Entities\Cart;

interface CartRepositoryInterface
{
    public function create(Cart $cart): Cart;

    public function save(Cart $cart): Cart;

    public function findById(int $cartId): ?Cart;
}