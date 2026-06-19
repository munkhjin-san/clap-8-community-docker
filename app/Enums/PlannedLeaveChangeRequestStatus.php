<?php

namespace App\Enums;

enum PlannedLeaveChangeRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => '申請中',
            self::Approved => '承認済み',
            self::Rejected => '却下',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
