<?php

namespace App\Infrastructure\Persistence\Eloquent\Mappers;

use App\Domain\Cart\Entities\Cart as DomainCart;
use App\Domain\Cart\Entities\CartItem as DomainCartItem;
use App\Models\Cart as CartModel;
use DateTimeImmutable;

final class CartMapper
{
    public function fromPersistence(CartModel $model): DomainCart
    {
        $items = $model->items
            ->map(static fn ($item) => DomainCartItem::make(
                (int) $item->product_id,
                (int) $item->quantity
            ))
            ->all();

        return DomainCart::reconstitute(
            (int) $model->id,
            $model->user_id ? (int) $model->user_id : null,
            $model->email,
            $items,
            DateTimeImmutable::createFromInterface($model->created_at),
            DateTimeImmutable::createFromInterface($model->updated_at),
            $model->first_reminder_sent_at?->toDateTimeImmutable(),
            $model->second_reminder_sent_at?->toDateTimeImmutable(),
            $model->third_reminder_sent_at?->toDateTimeImmutable(),
            $model->email_clicked_at?->toDateTimeImmutable(),
            $model->finalized_at?->toDateTimeImmutable(),
        );
    }

    /**
     * @return array{attributes: array<string, mixed>, items: array<int, array<string, mixed>>}
     */
    public function toPersistence(DomainCart $cart): array
    {
        return [
            'attributes' => [
                'user_id' => $cart->getUserId(),
                'email' => $cart->getEmail(),
                'first_reminder_sent_at' => $cart->getFirstReminderSentAt(),
                'second_reminder_sent_at' => $cart->getSecondReminderSentAt(),
                'third_reminder_sent_at' => $cart->getThirdReminderSentAt(),
                'email_clicked_at' => $cart->getEmailClickedAt(),
                'finalized_at' => $cart->getFinalizedAt(),
            ],
            'items' => array_map(
                static fn (DomainCartItem $item) => [
                    'product_id' => $item->getProductId(),
                    'quantity' => $item->getQuantity(),
                ],
                $cart->getItems()
            ),
        ];
    }
}

