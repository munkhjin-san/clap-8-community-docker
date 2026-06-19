<?php

namespace App\Enums;

enum ApplicationType: string
{
    case NameChange = 'name_change';
    case AddressChange = 'address_change';
    case DependentChange = 'dependent_change';
    case LeaveRequest = 'leave_request';
    case WorkLocationChange = 'work_location_change';
    case CommuteChange = 'commute_change';

    public function label(): string
    {
        return match ($this) {
            self::NameChange => '氏名変更',
            self::AddressChange => '住所変更',
            self::DependentChange => '扶養追加・削除',
            self::LeaveRequest => '休職申請',
            self::WorkLocationChange => '勤務地変更',
            self::CommuteChange => '交通費変更',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function profileChangeValues(): array
    {
        return [
            self::NameChange->value,
            self::AddressChange->value,
            self::DependentChange->value,
            self::WorkLocationChange->value,
        ];
    }
}
