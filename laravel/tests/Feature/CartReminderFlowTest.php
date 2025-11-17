<?php

namespace Tests\Feature;

use App\Application\Cart\Commands\AddCartItemCommand;
use App\Application\Cart\Commands\CreateCartCommand;
use App\Application\Cart\Commands\FinalizeCartCommand;
use App\Application\Cart\Commands\MarkCartEmailClickedCommand;
use App\Application\Cart\Commands\SendCartReminderCommand;
use App\Application\Cart\Handlers\AddCartItemHandler;
use App\Application\Cart\Handlers\CreateCartHandler;
use App\Application\Cart\Handlers\FinalizeCartHandler;
use App\Application\Cart\Handlers\MarkCartEmailClickedHandler;
use App\Application\Cart\Handlers\SendCartReminderHandler;
use App\Domain\Cart\Repositories\CartRepositoryInterface;
use App\Domain\Cart\ValueObjects\ReminderStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class CartReminderFlowTest extends TestCase
{
    use RefreshDatabase;

    private CartRepositoryInterface $repository;
    private CreateCartHandler $createHandler;
    private AddCartItemHandler $addItemHandler;
    private SendCartReminderHandler $sendReminderHandler;
    private MarkCartEmailClickedHandler $markClickedHandler;
    private FinalizeCartHandler $finalizeHandler;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cart.reminders.intervals' => [
                ReminderStep::FIRST->value => 0.016,
                ReminderStep::SECOND->value => 0.02,
                ReminderStep::THIRD->value => 0.03,
            ],
        ]);

        Mail::fake();
        Queue::fake();

        $this->repository = $this->app->make(CartRepositoryInterface::class);
        $this->createHandler = $this->app->make(CreateCartHandler::class);
        $this->addItemHandler = $this->app->make(AddCartItemHandler::class);
        $this->sendReminderHandler = $this->app->make(SendCartReminderHandler::class);
        $this->markClickedHandler = $this->app->make(MarkCartEmailClickedHandler::class);
        $this->finalizeHandler = $this->app->make(FinalizeCartHandler::class);
    }

    public function test_it_sends_all_three_reminders_automatically(): void
    {
        $cart = ($this->createHandler)(new CreateCartCommand(null, 'customer@example.com'));
        ($this->addItemHandler)(new AddCartItemCommand($cart->getId(), 101, 2));

        // dump('=== TEST: All Three Reminders - After Cart Creation ===');
        // dump(DB::table('carts')->where('id', $cart->getId())->first());
        // dump(DB::table('cart_items')->where('cart_id', $cart->getId())->get()->toArray());

        $this->backdateCart($cart->getId(), 2);
        $this->sendReminderHandler->__invoke(
            new SendCartReminderCommand($cart->getId(), ReminderStep::FIRST)
        );

        $cart = $this->repository->findById($cart->getId());
        $this->assertNotNull($cart->getFirstReminderSentAt());
        Mail::assertSent(\App\Mail\CartReminderMail::class, 1);

        // dump('=== TEST: All Three Reminders - After First Reminder ===');
        // dump(DB::table('carts')->where('id', $cart->getId())->first());

        $this->backdateReminder($cart->getId(), 'first_reminder_sent_at', 2);
        $this->sendReminderHandler->__invoke(
            new SendCartReminderCommand($cart->getId(), ReminderStep::SECOND)
        );

        $cart = $this->repository->findById($cart->getId());
        $this->assertNotNull($cart->getSecondReminderSentAt());
        Mail::assertSent(\App\Mail\CartReminderMail::class, 2);

        // dump('=== TEST: All Three Reminders - After Second Reminder ===');
        // dump(DB::table('carts')->where('id', $cart->getId())->first());

        $this->backdateReminder($cart->getId(), 'second_reminder_sent_at', 2);
        $this->sendReminderHandler->__invoke(
            new SendCartReminderCommand($cart->getId(), ReminderStep::THIRD)
        );

        $cart = $this->repository->findById($cart->getId());
        $this->assertNotNull($cart->getThirdReminderSentAt());
        Mail::assertSent(\App\Mail\CartReminderMail::class, 3);

        // dump('=== TEST: All Three Reminders - Final State (All Reminders Sent) ===');
        // dump(DB::table('carts')->where('id', $cart->getId())->first());
        // dump(DB::table('cart_items')->where('cart_id', $cart->getId())->get()->toArray());
    }

    public function test_it_stops_reminders_when_email_is_clicked(): void
    {
        $cart = ($this->createHandler)(new CreateCartCommand(null, 'customer@example.com'));
        ($this->addItemHandler)(new AddCartItemCommand($cart->getId(), 101, 1));

        // dump('=== TEST: Email Clicked - After Cart Creation ===');
        // dump(DB::table('carts')->where('id', $cart->getId())->first());

        $this->backdateCart($cart->getId(), 2);
        $this->sendReminderHandler->__invoke(
            new SendCartReminderCommand($cart->getId(), ReminderStep::FIRST)
        );

        Mail::assertSent(\App\Mail\CartReminderMail::class, 1);

        // dump('=== TEST: Email Clicked - After First Reminder ===');
        // dump(DB::table('carts')->where('id', $cart->getId())->first());

        ($this->markClickedHandler)(new MarkCartEmailClickedCommand($cart->getId()));

        $cart = $this->repository->findById($cart->getId());
        $this->assertNotNull($cart->getEmailClickedAt());
        $this->assertTrue($cart->isClosed());

        // dump('=== TEST: Email Clicked - After Marking Email Clicked ===');
        // dump(DB::table('carts')->where('id', $cart->getId())->first());

        $this->backdateReminder($cart->getId(), 'first_reminder_sent_at', 2);
        $this->sendReminderHandler->__invoke(
            new SendCartReminderCommand($cart->getId(), ReminderStep::SECOND)
        );

        Mail::assertSent(\App\Mail\CartReminderMail::class, 1);

        $cart = $this->repository->findById($cart->getId());
        $this->assertNull($cart->getSecondReminderSentAt());
        $this->assertNull($cart->getThirdReminderSentAt());

        // dump('=== TEST: Email Clicked - Final State (Second/Third Blocked) ===');
        // dump(DB::table('carts')->where('id', $cart->getId())->first());
    }

    public function test_it_stops_reminders_when_cart_is_finalized(): void
    {
        $cart = ($this->createHandler)(new CreateCartCommand(null, 'customer@example.com'));
        ($this->addItemHandler)(new AddCartItemCommand($cart->getId(), 101, 1));

        // dump('=== TEST: Finalized - After Cart Creation ===');
        // dump(DB::table('carts')->where('id', $cart->getId())->first());

        $this->backdateCart($cart->getId(), 2);
        $this->sendReminderHandler->__invoke(
            new SendCartReminderCommand($cart->getId(), ReminderStep::FIRST)
        );

        Mail::assertSent(\App\Mail\CartReminderMail::class, 1);

        // dump('=== TEST: Finalized - After First Reminder ===');
        // dump(DB::table('carts')->where('id', $cart->getId())->first());

        ($this->finalizeHandler)(new FinalizeCartCommand($cart->getId()));

        $cart = $this->repository->findById($cart->getId());
        $this->assertNotNull($cart->getFinalizedAt());
        $this->assertTrue($cart->isClosed());

        // dump('=== TEST: Finalized - After Finalizing Cart ===');
        // dump(DB::table('carts')->where('id', $cart->getId())->first());

        $this->backdateReminder($cart->getId(), 'first_reminder_sent_at', 2);
        $this->sendReminderHandler->__invoke(
            new SendCartReminderCommand($cart->getId(), ReminderStep::SECOND)
        );

        Mail::assertSent(\App\Mail\CartReminderMail::class, 1);

        $cart = $this->repository->findById($cart->getId());
        $this->assertNull($cart->getSecondReminderSentAt());
        $this->assertNull($cart->getThirdReminderSentAt());

        // dump('=== TEST: Finalized - Final State (Second/Third Blocked) ===');
        // dump(DB::table('carts')->where('id', $cart->getId())->first());
    }

    public function test_it_stops_reminders_after_second_when_email_clicked(): void
    {
        $cart = ($this->createHandler)(new CreateCartCommand(null, 'customer@example.com'));
        ($this->addItemHandler)(new AddCartItemCommand($cart->getId(), 101, 1));

        // dump('=== TEST: Clicked After Second - After Cart Creation ===');
        // dump(DB::table('carts')->where('id', $cart->getId())->first());

        $this->backdateCart($cart->getId(), 2);
        $this->sendReminderHandler->__invoke(
            new SendCartReminderCommand($cart->getId(), ReminderStep::FIRST)
        );

        // dump('=== TEST: Clicked After Second - After First Reminder ===');
        // dump(DB::table('carts')->where('id', $cart->getId())->first());

        $this->backdateReminder($cart->getId(), 'first_reminder_sent_at', 2);
        $this->sendReminderHandler->__invoke(
            new SendCartReminderCommand($cart->getId(), ReminderStep::SECOND)
        );

        Mail::assertSent(\App\Mail\CartReminderMail::class, 2);

        // dump('=== TEST: Clicked After Second - After Second Reminder ===');
        // dump(DB::table('carts')->where('id', $cart->getId())->first());

        ($this->markClickedHandler)(new MarkCartEmailClickedCommand($cart->getId()));

        // dump('=== TEST: Clicked After Second - After Marking Email Clicked ===');
        // dump(DB::table('carts')->where('id', $cart->getId())->first());

        $this->backdateReminder($cart->getId(), 'second_reminder_sent_at', 2);
        $this->sendReminderHandler->__invoke(
            new SendCartReminderCommand($cart->getId(), ReminderStep::THIRD)
        );

        Mail::assertSent(\App\Mail\CartReminderMail::class, 2);

        $cart = $this->repository->findById($cart->getId());
        $this->assertNull($cart->getThirdReminderSentAt());

        // dump('=== TEST: Clicked After Second - Final State (Third Blocked) ===');
        // dump(DB::table('carts')->where('id', $cart->getId())->first());
    }

    private function backdateCart(int $cartId, int $minutesAgo): void
    {
        \Illuminate\Support\Facades\DB::table('carts')
            ->where('id', $cartId)
            ->update(['created_at' => now()->subMinutes($minutesAgo)]);
    }

    private function backdateReminder(int $cartId, string $column, int $minutesAgo): void
    {
        \Illuminate\Support\Facades\DB::table('carts')
            ->where('id', $cartId)
            ->update([$column => now()->subMinutes($minutesAgo)]);
    }
}
