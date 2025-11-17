<?php

namespace App\Application\Cart\Commands;

final class FinalizeCartCommand
{
    public function __construct(public readonly int $cartId)
    {
    }
}

