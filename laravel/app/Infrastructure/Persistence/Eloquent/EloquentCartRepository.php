<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Cart\Entities\Cart;
use App\Domain\Cart\Repositories\CartRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Mappers\CartMapper;
use App\Models\Cart as CartModel;
use Illuminate\Support\Facades\DB;

final class EloquentCartRepository implements CartRepositoryInterface
{
    public function __construct(private CartMapper $mapper)
    {
    }

    public function create(Cart $cart): Cart
    {
        return DB::transaction(function () use ($cart) {
            $payload = $this->mapper->toPersistence($cart);

            $model = new CartModel();
            $model->fill($payload['attributes']);
            $model->save();

            $this->syncItems($model, $payload['items']);

            return $this->mapper->fromPersistence($model->fresh('items'));
        });
    }

    public function save(Cart $cart): Cart
    {
        if ($cart->getId() === null) {
            return $this->create($cart);
        }

        return DB::transaction(function () use ($cart) {
            $payload = $this->mapper->toPersistence($cart);

            $model = CartModel::query()
                ->whereKey($cart->getId())
                ->lockForUpdate()
                ->firstOrFail();

            $model->fill($payload['attributes']);
            $model->save();

            $this->syncItems($model, $payload['items']);

            return $this->mapper->fromPersistence($model->fresh('items'));
        });
    }

    public function findById(int $cartId): ?Cart
    {
        $model = CartModel::query()
            ->with('items')
            ->find($cartId);

        return $model ? $this->mapper->fromPersistence($model) : null;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function syncItems(CartModel $model, array $items): void
    {
        $model->items()->delete();

        if ($items === []) {
            return;
        }

        $model->items()->createMany($items);
    }
}

