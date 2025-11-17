<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendReminderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $cartId,
        private readonly ReminderStep $step,
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(SendCartReminderHandler $handler): void
    {
        $handler(new SendCartReminderCommand($this->cartId, $this->step));
    }
}
