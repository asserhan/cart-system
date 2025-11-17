<?php

namespace App\Domain\Cart\Exceptions;

use App\Domain\Cart\ValueObjects\ReminderStep;
use RuntimeException;

final class CartStateException extends RuntimeException
{
    public static function closed(): self
    {
        return new self('The cart is closed and cannot be modified.');
    }

    public static function reminderAlreadySent(ReminderStep $step): self
    {
        return new self(sprintf('The %s reminder has already been sent.', $step->value));
    }


}