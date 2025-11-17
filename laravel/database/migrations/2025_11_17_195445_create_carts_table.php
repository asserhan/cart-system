<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('email')->index();
            // Reminder scheduling timestamps
            $table->timestamp('first_reminder_sent_at')->nullable();
            $table->timestamp('second_reminder_sent_at')->nullable();
            $table->timestamp('third_reminder_sent_at')->nullable();

            $table->timestamp('email_clicked_at')->nullable();

            // If cart is converted to an order
            $table->timestamp('finalized_at')->nullable();

            $table->timestamps();

            // Optimize queries fetching non-finalized carts
            $table->index(['finalized_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
