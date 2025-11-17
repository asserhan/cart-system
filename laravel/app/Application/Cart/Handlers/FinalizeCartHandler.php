<?php

namespace App\Application\Cart\Handlers;

use App\Application\Cart\Commands\FinalizeCartCommand;
use App\Domain\Cart\Entities\Cart;
use App\Domain\Cart\Exceptions\CartNotFoundException;
use App\Domain\Cart\Repositories\CartRepositoryInterface;
use DateTimeImmutable;

final class FinalizeCartHandler
{
    public function __construct(private CartRepositoryInterface $repository)
    {
    }

    public function __invoke(FinalizeCartCommand $command): Cart
    {
        $cart = $this->repository->findById($command->cartId);

        if (!$cart) {
            throw CartNotFoundException::withId($command->cartId);
        }

        $cart->finalize(new DateTimeImmutable());

        return $this->repository->save($cart);
    }
}

