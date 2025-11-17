<?php

namespace App\Application\Cart\Commands;

final class MarkCartEmailClickedCommand
{
    public function __construct(public readonly int $cartId)
    {
    }
}

