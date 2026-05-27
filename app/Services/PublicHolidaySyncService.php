<?php

namespace App\Services;

use App\Models\PublicHoliday;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PublicHolidaySyncService
{
    public const SOURCE_URL = 'https://www8.cao.go.jp/chosei/shukujitsu/syukujitsu.csv';

    public function sync(?string $sourceUrl = null): int
    {
        $response = Http::timeout(30)->get($sourceUrl ?: self::SOURCE_URL);

        if (! $response->successful()) {
            throw new RuntimeException('祝日CSVの取得に失敗しました。');
        }

        $rows = $this->parse($response->body());

        if ($rows === []) {
            throw new RuntimeException('祝日CSVを解析できませんでした。');
        }

        $now = now();
        $dates = array_column($rows, 'date');

        DB::transaction(function () use ($rows, $now, $dates) {
            PublicHoliday::query()->where('date', '<', '2020-01-01')->delete();

            if ($dates !== []) {
                PublicHoliday::query()
                    ->where('date', '>=', '2020-01-01')
                    ->whereNotIn('date', $dates)
                    ->delete();
            }

            PublicHoliday::query()->upsert(
                array_map(static fn (array $row) => [
                    'date' => $row['date'],
                    'holiday_name' => $row['holiday_name'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $rows),
                ['date'],
                ['holiday_name', 'updated_at']
            );
        });

        return count($rows);
    }

    /**
     * @return array<int, array{date: string, holiday_name: string}>
     */
    private function parse(string $csv): array
    {
        $utf8Csv = mb_convert_encoding($csv, 'UTF-8', 'SJIS-win,UTF-8');
        $lines = preg_split("/(\r\n|\n|\r)/", trim($utf8Csv)) ?: [];

        if (count($lines) <= 1) {
            return [];
        }

        $rows = [];

        foreach (array_slice($lines, 1) as $line) {
            if (trim($line) === '') {
                continue;
            }

            $columns = str_getcsv($line);

            if (count($columns) < 2) {
                continue;
            }

            $date = trim($columns[0]);
            $holidayName = trim($columns[1]);
            $holidayDate = Carbon::createFromFormat('Y/n/j', $date);

            if (! $holidayDate || $holidayDate->year < 2020) {
                continue;
            }

            if ($holidayName === '休日') {
                $holidayName = '振替休日';
            }

            $rows[$holidayDate->toDateString()] = [
                'date' => $holidayDate->toDateString(),
                'holiday_name' => $holidayName,
            ];
        }

        ksort($rows);

        return array_values($rows);
    }
}