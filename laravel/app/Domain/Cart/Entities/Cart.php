<?php

namespace App\Domain\Cart\Entities;

use App\Domain\Cart\Exceptions\CartStateException;
use App\Domain\Cart\ValueObjects\ReminderStep;
use DateTimeImmutable;
use InvalidArgumentException;

final class Cart
{
    private ?int $id;
    private ?int $userId;
    private string $email;

    /** @var CartItem[] */
    private array $items = [];

    private ?DateTimeImmutable $firstReminderSentAt;
    private ?DateTimeImmutable $secondReminderSentAt;
    private ?DateTimeImmutable $thirdReminderSentAt;
    private ?DateTimeImmutable $emailClickedAt;
    private ?DateTimeImmutable $finalizedAt;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    /**
     * @param CartItem[] $items
     */
    private function __construct(
        ?int $id,
        ?int $userId,
        string $email,
        array $items,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?DateTimeImmutable $firstReminderSentAt = null,
        ?DateTimeImmutable $secondReminderSentAt = null,
        ?DateTimeImmutable $thirdReminderSentAt = null,
        ?DateTimeImmutable $emailClickedAt = null,
        ?DateTimeImmutable $finalizedAt = null,
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->email = self::normalizeEmail($email);
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->firstReminderSentAt = $firstReminderSentAt;
        $this->secondReminderSentAt = $secondReminderSentAt;
        $this->thirdReminderSentAt = $thirdReminderSentAt;
        $this->emailClickedAt = $emailClickedAt;
        $this->finalizedAt = $finalizedAt;
        $this->setItems($items);
    }

    public static function create(?int $userId, string $email): self
    {
        $now = new DateTimeImmutable();

        return new self(
            null,
            $userId,
            $email,
            [],
            $now,
            $now
        );
    }

    /**
     * @param CartItem[] $items
     */
    public static function reconstitute(
        ?int $id,
        ?int $userId,
        string $email,
        array $items,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?DateTimeImmutable $firstReminderSentAt = null,
        ?DateTimeImmutable $secondReminderSentAt = null,
        ?DateTimeImmutable $thirdReminderSentAt = null,
        ?DateTimeImmutable $emailClickedAt = null,
        ?DateTimeImmutable $finalizedAt = null,
    ): self {
        return new self(
            $id,
            $userId,
            $email,
            $items,
            $createdAt,
            $updatedAt,
            $firstReminderSentAt,
            $secondReminderSentAt,
            $thirdReminderSentAt,
            $emailClickedAt,
            $finalizedAt
        );
    }

    private static function normalizeEmail(string $email): string
    {
        $normalized = trim($email);

        if ($normalized === '') {
            throw new InvalidArgumentException('Email cannot be empty.');
        }

        return function_exists('mb_strtolower')
            ? mb_strtolower($normalized)
            : strtolower($normalized);
    }

    /**
     * @param CartItem[] $items
     */
    private function setItems(array $items): void
    {
        $this->items = [];

        foreach ($items as $item) {
            if (!$item instanceof CartItem) {
                throw new InvalidArgumentException('Invalid cart item provided.');
            }

            $this->items[] = $item;
        }
    }

    public function addItem(int $productId, int $quantity = 1): void
    {
        $this->assertOpen();

        foreach ($this->items as $item) {
            if ($item->getProductId() === $productId) {
                $item->increase($quantity);
                $this->touch();

                return;
            }
        }

        $this->items[] = CartItem::make($productId, $quantity);
        $this->touch();
    }

    private function assertOpen(): void
    {
        if ($this->isClosed()) {
            throw CartStateException::closed();
        }
    }

    public function isClosed(): bool
    {
        return $this->isFinalized() || $this->emailClickedAt !== null;
    }

    public function isFinalized(): bool
    {
        return $this->finalizedAt !== null;
    }

    public function markReminderSent(ReminderStep $step, DateTimeImmutable $sentAt): void
    {
        $this->assertOpen();

        if ($this->hasReminderBeenSent($step)) {
            throw CartStateException::reminderAlreadySent($step);
        }

        $this->setReminderTimestamp($step, $sentAt);
        $this->touch();
    }

    public function hasReminderBeenSent(ReminderStep $step): bool
    {
        return $this->getReminderTimestamp($step) !== null;
    }

    public function getReminderTimestamp(ReminderStep $step): ?DateTimeImmutable
    {
        return match ($step) {
            ReminderStep::FIRST => $this->firstReminderSentAt,
            ReminderStep::SECOND => $this->secondReminderSentAt,
            ReminderStep::THIRD => $this->thirdReminderSentAt,
        };
    }

    private function setReminderTimestamp(ReminderStep $step, ?DateTimeImmutable $value): void
    {
        switch ($step) {
            case ReminderStep::FIRST:
                $this->firstReminderSentAt = $value;

                break;
            case ReminderStep::SECOND:
                $this->secondReminderSentAt = $value;

                break;
            case ReminderStep::THIRD:
                $this->thirdReminderSentAt = $value;

                break;
        }
    }

    public function markEmailClicked(DateTimeImmutable $clickedAt): void
    {
        if ($this->emailClickedAt !== null) {
            return;
        }

        $this->emailClickedAt = $clickedAt;
        $this->touch();
    }

    public function finalize(DateTimeImmutable $finalizedAt): void
    {
        if ($this->isFinalized()) {
            return;
        }

        $this->finalizedAt = $finalizedAt;
        $this->touch();
    }

    /**
     * @return CartItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getFirstReminderSentAt(): ?DateTimeImmutable
    {
        return $this->firstReminderSentAt;
    }

    public function getSecondReminderSentAt(): ?DateTimeImmutable
    {
        return $this->secondReminderSentAt;
    }

    public function getThirdReminderSentAt(): ?DateTimeImmutable
    {
        return $this->thirdReminderSentAt;
    }

    public function getEmailClickedAt(): ?DateTimeImmutable
    {
        return $this->emailClickedAt;
    }

    public function getFinalizedAt(): ?DateTimeImmutable
    {
        return $this->finalizedAt;
    }

    public function getNextPendingStep(): ?ReminderStep
    {
        foreach (ReminderStep::ordered() as $step) {
            if (!$this->hasReminderBeenSent($step)) {
                return $step;
            }
        }

        return null;
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}

