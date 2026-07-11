<?php

namespace Tests\Unit;

use App\Models\CalendarFacility;
use PHPUnit\Framework\TestCase;

class CalendarFacilityTest extends TestCase
{
    public function test_calendar_option_preserves_the_legacy_slot(): void
    {
        $facility = new CalendarFacility([
            'type' => CalendarFacility::TYPE_ROOM,
            'slot' => 6,
            'label' => 'フジメンビル',
            'active' => true,
        ]);

        $this->assertSame([
            'label' => 'フジメンビル',
            'value' => 6,
            'selected' => false,
            'selectable' => true,
        ], $facility->calendarOption());
    }

    public function test_inactive_facility_remains_visible_but_not_selectable(): void
    {
        $facility = new CalendarFacility([
            'type' => CalendarFacility::TYPE_CAR,
            'slot' => 0,
            'label' => '利用停止中の車両',
            'active' => false,
        ]);

        $option = $facility->calendarOption();

        $this->assertSame(0, $option['value']);
        $this->assertFalse($option['selectable']);
    }
}
