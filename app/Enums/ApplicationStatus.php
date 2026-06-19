<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Submitted = 'submitted';
    case Reviewing = 'reviewing';
    case Confirmed = 'confirmed';
    case Denied = 'denied';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => '申請中',
            self::Reviewing => 'レビュー中',
            self::Confirmed => '承認済み',
            self::Denied => '却下',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function reviewableValues(): array
    {
        return [
            self::Reviewing->value,
            self::Confirmed->value,
            self::Denied->value,
        ];
    }
}
