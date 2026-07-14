<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ガソリン単価（全社共通・円/L）の履歴。
 * 1レコード = 1つの適用開始日を持つ単価。現在値は effective_from <= 今日 の最新行。
 * マイカーガソリン代 = (走行距離 ÷ 実燃費) × この単価。
 */
class GasolineRate extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'rate' => 'float',
        'effective_from' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 指定日（省略時は今日）時点で有効な円/Lのガソリン単価を返す。未設定なら null。
     */
    public static function currentRate(?string $onDate = null): ?float
    {
        $date = $onDate ?? Carbon::now()->toDateString();

        return static::query()
            ->whereDate('effective_from', '<=', $date)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->value('rate');
    }
}
