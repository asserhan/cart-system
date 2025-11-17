<?php

namespace App\Application\Cart\Handlers;

use App\Application\Cart\Commands\AddCartItemCommand;
use App\Domain\Cart\Entities\Cart;
use App\Domain\Cart\Exceptions\CartNotFoundException;
use App\Domain\Cart\Repositories\CartRepositoryInterface;

final class AddCartItemHandler
{
    public function __construct(private CartRepositoryInterface $repository)
    {
    }

    public function __invoke(AddCartItemCommand $command): Cart
    {
        $cart = $this->repository->findById($command->cartId);

        if (!$cart) {
            throw CartNotFoundException::withId($command->cartId);
        }

        $cart->addItem($command->productId, $command->quantity);

        return $this->repository->save($cart);
    }
}

