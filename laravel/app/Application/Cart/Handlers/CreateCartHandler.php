<?php

namespace App\Application\Cart\Handlers;

use App\Application\Cart\Commands\CreateCartCommand;
use App\Application\Cart\Jobs\SendReminderJob;
use App\Domain\Cart\Entities\Cart;
use App\Domain\Cart\Repositories\CartRepositoryInterface;
use App\Domain\Cart\Services\CartReminderService;
use App\Domain\Cart\ValueObjects\ReminderStep;
use Carbon\CarbonImmutable;

final class CreateCartHandler
{
    public function __construct(
        private CartRepositoryInterface $repository,
        private CartReminderService $reminderService,
    ) {
    }

    public function __invoke(CreateCartCommand $command): Cart
    {
        $cart = Cart::create($command->userId, $command->email);
        $cart = $this->repository->create($cart);

        $this->queueReminder($cart, ReminderStep::FIRST);

        return $cart;
    }

    private function queueReminder(Cart $cart, ReminderStep $step): void
    {
        if ($cart->getId() === null || !$this->reminderService->canSchedule($step)) {
            return;
        }

        $dueAt = $this->reminderService->calculateDueAt($cart, $step);

        if ($dueAt === null) {
            return;
        }

        SendReminderJob::dispatch($cart->getId(), $step)
            ->onQueue(config('cart.reminders.queue'))
            ->delay(CarbonImmutable::instance($dueAt));
    }
}

