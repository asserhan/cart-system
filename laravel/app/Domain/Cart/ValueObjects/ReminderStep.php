<?php

namespace App\Domain\Cart\ValueObjects;

enum ReminderStep: string
{
    case FIRST = 'first';
    case SECOND = 'second';
    case THIRD = 'third';

    /**
     * @return ReminderStep[]
     */
    public static function ordered(): array
    {
        return [
            self::FIRST,
            self::SECOND,
            self::THIRD,
        ];
    }

    public function next(): ?self
    {
        return match ($this) {
            self::FIRST => self::SECOND,
            self::SECOND => self::THIRD,
            self::THIRD => null,
        };
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}

