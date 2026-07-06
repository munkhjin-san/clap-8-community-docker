<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCommunity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class shiftType extends Model
{
    use BelongsToCommunity;

    use SoftDeletes;

    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'hours' => 'float',
        'full_day' => 'integer',
        'active' => 'boolean',
    ];

    public const LEGAL_HOLIDAY_ID = 18;
    public const UNUSED_IDS = [17];

    /**
     * Stable code-facing classification (system meaning), independent of the
     * customizable record id. See docs/shift_type_hardcoding_inventory.md.
     */
    public const CATEGORY_WORK = 'work';
    public const CATEGORY_DAY_OFF = 'day_off';
    public const CATEGORY_ABSENCE = 'absence';
    public const CATEGORY_PLANNED_PAID_LEAVE = 'planned_paid_leave';
    public const CATEGORY_ANNUAL_LEAVE_FULL = 'annual_leave_full';
    public const CATEGORY_ANNUAL_LEAVE_HALF = 'annual_leave_half';
    public const CATEGORY_ANNUAL_LEAVE_HOURLY = 'annual_leave_hourly';
    // Special leaves are distinct categories (they map to distinct payroll fields:
    // condolence_holiday / special_holiday / oda_holiday). Use the SPECIAL_LEAVE
    // group below where "any special leave" is meant.
    public const CATEGORY_SPECIAL_LEAVE_CONDOLENCE = 'special_leave_condolence';
    public const CATEGORY_SPECIAL_LEAVE_TRANSFER = 'special_leave_transfer';
    public const CATEGORY_SPECIAL_LEAVE_ODA = 'special_leave_oda';
    public const CATEGORY_COMP_HOLIDAY = 'comp_holiday';
    public const CATEGORY_LEGAL_HOLIDAY = 'legal_holiday';
    public const CATEGORY_HOLIDAY_WORK = 'holiday_work';
    // 特別休暇 (generic special holiday, glowd id 27): a full-day leave with its
    // OWN payroll field (attendance.special_holiday -> special_hours) and a
    // per-user general_position (C-G) annual quota — distinct from the
    // condolence/transfer/ODA trio above. Absent from this test DB; live in prod.
    public const CATEGORY_SPECIAL_HOLIDAY = 'special_holiday';

    // Unified "special leave" category for the admin catalog. Condolence/transfer/
    // ODA are calculated identically (work_time_day × days); they differ only in
    // which attendance-report column they feed. So admins now assign ONE
    // `special_leave` category; the seeded 慶弔/転勤/ODA records keep their
    // fine-grained sub-categories (below) purely so AutoAttendanceConfirm can keep
    // routing them to the 慶弔休暇 / ODA休暇 / 特別休暇 report columns.
    public const CATEGORY_SPECIAL_LEAVE = 'special_leave';

    // Fine categories that the catalog folds under the single "特別休暇" umbrella
    // (so admins assign one category) but that are KEPT on their seeded records so
    // payroll/report logic keeps routing each to its own attendance column:
    //   condolence -> 慶弔休暇, transfer/(special_holiday) -> 特別休暇, oda -> ODA休暇,
    //   comp_holiday(代休) -> 代休 column + the overtime (all_worked_time) term.
    // NOTE: comp_holiday is folded here for the CATALOG/UI only; it is intentionally
    // NOT in the SPECIAL_LEAVE calc group below (its calc rule differs).
    public const SPECIAL_LEAVE_SUBTYPES = [
        self::CATEGORY_SPECIAL_LEAVE_CONDOLENCE,
        self::CATEGORY_SPECIAL_LEAVE_TRANSFER,
        self::CATEGORY_SPECIAL_LEAVE_ODA,
        self::CATEGORY_COMP_HOLIDAY,
    ];

    /** Group: any special leave (the unified category + the seeded sub-types). */
    public const SPECIAL_LEAVE = [
        self::CATEGORY_SPECIAL_LEAVE,
        self::CATEGORY_SPECIAL_LEAVE_CONDOLENCE,
        self::CATEGORY_SPECIAL_LEAVE_TRANSFER,
        self::CATEGORY_SPECIAL_LEAVE_ODA,
    ];

    /** Categories whose meaning is disambiguated by the `hours` attribute. */
    public const HOURLY_CATEGORIES = [
        self::CATEGORY_ANNUAL_LEAVE_HOURLY,
        self::CATEGORY_HOLIDAY_WORK,
    ];

    /**
     * The fixed, admin-assignable category catalog (single source for the CRUD
     * selector). Order = display order; `hours` flags the categories that take
     * an hours value. The set is intentionally fixed (Japan labour taxonomy).
     *
     * @return array<int, array{value:string, label:string, hours:bool}>
     */
    public static function categoryCatalog(): array
    {
        return [
            ['value' => self::CATEGORY_WORK,                     'label' => '勤務',        'hours' => false],
            ['value' => self::CATEGORY_DAY_OFF,                  'label' => '休日',        'hours' => false],
            ['value' => self::CATEGORY_LEGAL_HOLIDAY,            'label' => '法定休日',    'hours' => false],
            ['value' => self::CATEGORY_HOLIDAY_WORK,             'label' => '休日出勤',    'hours' => true],
            ['value' => self::CATEGORY_PLANNED_PAID_LEAVE,       'label' => '計画有給',    'hours' => false],
            ['value' => self::CATEGORY_ANNUAL_LEAVE_FULL,        'label' => '年休（1日）', 'hours' => false],
            ['value' => self::CATEGORY_ANNUAL_LEAVE_HALF,        'label' => '年休（半日）','hours' => false],
            ['value' => self::CATEGORY_ANNUAL_LEAVE_HOURLY,      'label' => '年休（時間）','hours' => true],
            ['value' => self::CATEGORY_SPECIAL_LEAVE,            'label' => '特別休暇（慶弔・転勤・ODA・代休）', 'hours' => false],
            ['value' => self::CATEGORY_SPECIAL_HOLIDAY,          'label' => '特別休暇',    'hours' => false],
            ['value' => self::CATEGORY_ABSENCE,                  'label' => '休業',        'hours' => false],
        ];
    }

    /**
     * Valid category keys for validation. Includes the catalog values PLUS the
     * seeded special-leave sub-types, which are no longer offered in the catalog
     * but still exist on the 慶弔/転勤/ODA records (so editing them validates).
     *
     * @return array<int,string>
     */
    public static function categoryKeys(): array
    {
        return array_values(array_unique(array_merge(
            array_column(self::categoryCatalog(), 'value'),
            self::SPECIAL_LEAVE_SUBTYPES,
        )));
    }

    /** @var array<string, array<int,int>> per-request cache keyed by community|categories */
    protected static array $categoryIdCache = [];

    /**
     * Shift-type ids in the ACTIVE community matching the given category(ies).
     * Use instead of hardcoded id arrays. Memoized per request + community.
     *
     * @param  string|array<int,string>  $categories
     * @return array<int,int>
     */
    public static function idsFor(string|array $categories): array
    {
        $cats = array_values((array) $categories);
        sort($cats);
        $communityId = app()->bound(\App\Services\Community\CommunityContext::class)
            ? (app(\App\Services\Community\CommunityContext::class)->communityId() ?? 0)
            : 0;
        $key = $communityId . '|' . implode(',', $cats);

        return static::$categoryIdCache[$key] ??= static::query()
            ->whereIn('category', $cats)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * The single shift-type id for a category (+ optional hours amount), in the
     * active community. For disambiguating the hourly leave / holiday-work types.
     */
    public static function idFor(string $category, int|float|null $hours = null): ?int
    {
        $id = static::query()
            ->where('category', $category)
            ->when($hours !== null, fn ($q) => $q->where('hours', $hours))
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    public function isCategory(string|array $categories): bool
    {
        return $this->category !== null && in_array($this->category, (array) $categories, true);
    }
}
