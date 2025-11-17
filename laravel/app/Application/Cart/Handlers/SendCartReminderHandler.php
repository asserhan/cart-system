<?php

namespace App\Application\Cart\Handlers;

use App\Application\Cart\Commands\SendCartReminderCommand;
use App\Application\Cart\Jobs\SendReminderJob;
use App\Domain\Cart\Entities\Cart;
use App\Domain\Cart\Repositories\CartRepositoryInterface;
use App\Domain\Cart\Services\CartReminderService;
use App\Domain\Cart\ValueObjects\ReminderStep;
use App\Mail\CartReminderMail;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Support\Facades\Mail;

final class SendCartReminderHandler
{
    public function __construct(
        private CartRepositoryInterface $repository,
        private CartReminderService $reminderService,
    ) {
    }

    public function __invoke(SendCartReminderCommand $command): void
    {
        $cart = $this->repository->findById($command->cartId);

        if (!$cart) {
            return;
        }

        $now = new DateTimeImmutable();

        if (!$this->reminderService->shouldSendReminder($cart, $command->step, $now)) {
            return;
        }

        Mail::to($cart->getEmail())->send(new CartReminderMail($cart, $command->step));

        $cart->markReminderSent($command->step, $now);
        $cart = $this->repository->save($cart);

        $this->scheduleNext($cart, $command->step->next());
    }

    private function scheduleNext(Cart $cart, ?ReminderStep $nextStep): void
    {
        if ($nextStep === null || !$this->reminderService->canSchedule($nextStep)) {
            return;
        }

        $dueAt = $this->reminderService->calculateDueAt($cart, $nextStep);

        if ($dueAt === null || $cart->getId() === null) {
            return;
        }

        SendReminderJob::dispatch($cart->getId(), $nextStep)
            ->onQueue(config('cart.reminders.queue'))
            ->delay(CarbonImmutable::instance($dueAt));
    }
}

