<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flow_definitions', function (Blueprint $table) {
            // Highest per-app record number ever handed out (never decremented → numbers never reused).
            $table->unsignedInteger('record_seq')->default(0)->after('id');
        });

        Schema::table('flow_records', function (Blueprint $table) {
            $table->unsignedInteger('record_number')->nullable()->after('flow_definition_id');
        });

        Schema::table('flow_views', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('view_mode');
        });

        // ---- Backfill per-app record numbers + counters + default views ----
        $now = Carbon::now();
        foreach (DB::table('flow_definitions')->pluck('id') as $defId) {
            $n = 0;
            foreach (DB::table('flow_records')->where('flow_definition_id', $defId)->orderBy('id')->pluck('id') as $recId) {
                $n++;
                DB::table('flow_records')->where('id', $recId)->update(['record_number' => $n]);
            }
            DB::table('flow_definitions')->where('id', $defId)->update(['record_seq' => $n]);

            $views = DB::table('flow_views')->where('flow_definition_id', $defId);
            if (!$views->exists()) {
                DB::table('flow_views')->insert([
                    'flow_definition_id' => $defId,
                    'name' => 'すべて',
                    'view_mode' => 'table',
                    'is_default' => true,
                    'columns' => null,
                    'filters' => null,
                    'sort' => null,
                    'created_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } elseif (!(clone $views)->where('is_default', true)->exists()) {
                $first = (clone $views)->orderBy('id')->first();
                if ($first) {
                    DB::table('flow_views')->where('id', $first->id)->update(['is_default' => true]);
                }
            }
        }

        // Hard backstop against duplicate numbers within an app.
        Schema::table('flow_records', function (Blueprint $table) {
            $table->unique(['flow_definition_id', 'record_number'], 'flow_record_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('flow_records', function (Blueprint $table) {
            $table->dropUnique('flow_record_number_unique');
            $table->dropColumn('record_number');
        });
        Schema::table('flow_definitions', function (Blueprint $table) {
            $table->dropColumn('record_seq');
        });
        Schema::table('flow_views', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
};
