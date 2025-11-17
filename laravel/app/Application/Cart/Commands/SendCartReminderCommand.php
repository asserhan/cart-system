<?php

namespace App\Application\Cart\Commands;

use App\Domain\Cart\ValueObjects\ReminderStep;

final class SendCartReminderCommand
{
    public function __construct(
        public readonly int $cartId,
        public readonly ReminderStep $step,
    ) {
    }
}

