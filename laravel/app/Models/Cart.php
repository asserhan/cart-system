<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'first_reminder_sent_at',
        'second_reminder_sent_at',
        'third_reminder_sent_at',
        'email_clicked_at',
        'finalized_at',
    ];

    protected $casts = [
        'first_reminder_sent_at' => 'datetime',
        'second_reminder_sent_at' => 'datetime',
        'third_reminder_sent_at' => 'datetime',
        'email_clicked_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
