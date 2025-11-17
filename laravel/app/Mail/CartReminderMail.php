<?php

namespace App\Mail;

use App\Domain\Cart\Entities\Cart;
use App\Domain\Cart\ValueObjects\ReminderStep;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CartReminderMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Cart $cart,
        public ReminderStep $step,
    ) {
    }

    public function build(): self
    {
        return $this->subject('Complete your cart with us')
            ->view('emails.cart-reminder', [
                'cart' => $this->cart,
                'step' => $this->step,
            ]);
    }
}

