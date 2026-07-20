<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCommunity;
use Illuminate\Database\Eloquent\Model;

class CalendarFacility extends Model
{
    use BelongsToCommunity;

    public const TYPE_ROOM = 'room';

    public const TYPE_CAR = 'car';

    protected $guarded = [];

    protected $casts = [
        'slot' => 'integer',
        'active' => 'boolean',
    ];

    public function adminPayload(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'slot' => $this->slot,
            'label' => $this->label,
            'active' => $this->active,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    public function calendarOption(): array
    {
        return [
            'label' => $this->label,
            'value' => $this->slot,
            'selected' => false,
            'selectable' => $this->active,
        ];
    }
}
