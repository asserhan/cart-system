<?php

namespace App\Domain\Cart\Services;

use App\Domain\Cart\Entities\Cart;
use App\Domain\Cart\ValueObjects\ReminderStep;
use DateTimeImmutable;

final class CartReminderService
{
    /**
     * @param array<string, int> $intervals
     */
    public function __construct(private array $intervals)
    {
    }

    public function canSchedule(ReminderStep $step): bool
    {
        return $this->intervalFor($step) !== null;
    }

    public function intervalFor(ReminderStep $step): ?int
    {
        $interval = $this->intervals[$step->value] ?? null;

        return $interval === null ? null : (int) $interval;
    }

    public function shouldSendReminder(Cart $cart, ReminderStep $step, ?DateTimeImmutable $now = null): bool
    {
        $now ??= new DateTimeImmutable();

        if (!$this->canSchedule($step)) {
            return false;
        }

        if ($cart->isClosed()) {
            return false;
        }

        if (!$this->dependenciesSatisfied($cart, $step)) {
            return false;
        }

        if ($cart->hasReminderBeenSent($step)) {
            return false;
        }

        $dueAt = $this->calculateDueAt($cart, $step);

        if ($dueAt === null) {
            return false;
        }

        return $now >= $dueAt;
    }

    public function calculateDueAt(Cart $cart, ReminderStep $step): ?DateTimeImmutable
    {
        $interval = $this->intervalFor($step);

        if ($interval === null) {
            return null;
        }

        return match ($step) {
            ReminderStep::FIRST => $cart->getCreatedAt()->modify("+{$interval} hours"),
            ReminderStep::SECOND => $cart->getFirstReminderSentAt()?->modify("+{$interval} hours"),
            ReminderStep::THIRD => $cart->getSecondReminderSentAt()?->modify("+{$interval} hours"),
        };
    }

    public function secondsUntil(Cart $cart, ReminderStep $step, ?DateTimeImmutable $now = null): ?int
    {
        $now ??= new DateTimeImmutable();

        $dueAt = $this->calculateDueAt($cart, $step);

        if ($dueAt === null) {
            return null;
        }

        return max(0, $dueAt->getTimestamp() - $now->getTimestamp());
    }

    private function dependenciesSatisfied(Cart $cart, ReminderStep $step): bool
    {
        return match ($step) {
            ReminderStep::FIRST => true,
            ReminderStep::SECOND => $cart->hasReminderBeenSent(ReminderStep::FIRST),
            ReminderStep::THIRD => $cart->hasReminderBeenSent(ReminderStep::SECOND),
        };
    }
}

