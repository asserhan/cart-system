<?php

namespace App\Application\Cart\Handlers;

use App\Application\Cart\Commands\MarkCartEmailClickedCommand;
use App\Domain\Cart\Entities\Cart;
use App\Domain\Cart\Exceptions\CartNotFoundException;
use App\Domain\Cart\Repositories\CartRepositoryInterface;
use DateTimeImmutable;

final class MarkCartEmailClickedHandler
{
    public function __construct(private CartRepositoryInterface $repository)
    {
    }

    public function __invoke(MarkCartEmailClickedCommand $command): Cart
    {
        $cart = $this->repository->findById($command->cartId);

        if (!$cart) {
            throw CartNotFoundException::withId($command->cartId);
        }

        $cart->markEmailClicked(new DateTimeImmutable());

        return $this->repository->save($cart);
    }
}

