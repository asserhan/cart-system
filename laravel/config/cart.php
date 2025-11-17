<?php

use App\Domain\Cart\ValueObjects\ReminderStep;

return [
    'reminders' => [
        'queue' => env('CART_REMINDER_QUEUE', 'cart-reminders'),
        'intervals' => [
            ReminderStep::FIRST->value => (int) env('CART_REMINDER_FIRST_HOURS', 4),
            ReminderStep::SECOND->value => (int) env('CART_REMINDER_SECOND_HOURS', 24),
            ReminderStep::THIRD->value => (int) env('CART_REMINDER_THIRD_HOURS', 72),
        ],
    ],
];

