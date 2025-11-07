<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_cases', function (Blueprint $table) {
            $table->string('kind', 16)->default('PIPELINE')->index();
            $table->string('stage', 16)->nullable()->index();
            $table->string('delivery_status', 32)->nullable()->index();
            $table->decimal('probability', 5, 4)->nullable();
        });

        // Backfill existing records to preserve behaviour
        $stageMap = [
            '目標値'      => ['kind' => 'PLAN',     'stage' => null,    'delivery_status' => null,                 'probability' => null, 'status' => '目標値'],
            '★竣工済'    => ['kind' => 'ACTUAL',   'stage' => 'WON',   'delivery_status' => 'COMPLETED',          'probability' => 1,    'status' => '★竣工済'],
            '①受注済未竣工' => ['kind' => 'ACTUAL', 'stage' => 'WON', 'delivery_status' => 'ORDERED_NOT_COMPLETED', 'probability' => 1,    'status' => '①受注済未竣工'],
            '②確度A'     => ['kind' => 'PIPELINE', 'stage' => 'A',     'delivery_status' => null,                 'probability' => 0.9,  'status' => '②確度A'],
            '③確度B'     => ['kind' => 'PIPELINE', 'stage' => 'B',     'delivery_status' => null,                 'probability' => 0.7,  'status' => '③確度B'],
            '④確度C'     => ['kind' => 'PIPELINE', 'stage' => 'C',     'delivery_status' => null,                 'probability' => 0.5,  'status' => '④確度C'],
            '⑤確度D、E'  => ['kind' => 'PIPELINE', 'stage' => 'D',     'delivery_status' => null,                 'probability' => 0.3,  'status' => '⑤確度D'],
            '⑤確度D'     => ['kind' => 'PIPELINE', 'stage' => 'D',     'delivery_status' => null,                 'probability' => 0.3,  'status' => '⑤確度D'],
            '⑥確度E'     => ['kind' => 'PIPELINE', 'stage' => 'E',     'delivery_status' => null,                 'probability' => 0.1,  'status' => '⑥確度E'],
        ];

        $cases = DB::table('project_cases')->select('id', 'status')->get();
        foreach ($cases as $case) {
            $mapping = $stageMap[$case->status] ?? ['kind' => 'PIPELINE', 'stage' => null, 'delivery_status' => null, 'probability' => null];
            DB::table('project_cases')
                ->where('id', $case->id)
                ->update($mapping);
        }
    }

    public function down(): void
    {
        Schema::table('project_cases', function (Blueprint $table) {
            $table->dropColumn(['kind', 'stage', 'delivery_status', 'probability']);
        });
    }
};
