<?php

use App\Models\Incident;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('incident_logs') && !Schema::hasTable('update_logs')) {
            Schema::rename('incident_logs', 'update_logs');
        }

        if (!Schema::hasTable('update_logs')) {
            return;
        }

        Schema::table('update_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('update_logs', 'loggable_type')) {
                $table->string('loggable_type')->nullable()->after('id');
            }

            if (!Schema::hasColumn('update_logs', 'loggable_id')) {
                $table->unsignedBigInteger('loggable_id')->nullable()->after('loggable_type');
            }
        });

        if (Schema::hasColumn('update_logs', 'incident_id')) {
            DB::table('update_logs')
                ->whereNull('loggable_type')
                ->update(['loggable_type' => Incident::class]);

            DB::table('update_logs')
                ->whereNull('loggable_id')
                ->update(['loggable_id' => DB::raw('incident_id')]);

            Schema::table('update_logs', function (Blueprint $table) {
                $table->dropColumn('incident_id');
            });
        }

        Schema::table('update_logs', function (Blueprint $table) {
            $table->index(['loggable_type', 'loggable_id']);
            $table->index(['loggable_type', 'loggable_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('update_logs')) {
            return;
        }

        Schema::table('update_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('update_logs', 'incident_id')) {
                $table->unsignedBigInteger('incident_id')->nullable()->after('id')->index();
            }
        });

        DB::table('update_logs')
            ->where('loggable_type', Incident::class)
            ->whereNull('incident_id')
            ->update(['incident_id' => DB::raw('loggable_id')]);

        Schema::table('update_logs', function (Blueprint $table) {
            if (Schema::hasColumn('update_logs', 'loggable_type')) {
                $table->dropColumn('loggable_type');
            }

            if (Schema::hasColumn('update_logs', 'loggable_id')) {
                $table->dropColumn('loggable_id');
            }

            $table->index(['incident_id', 'created_at']);
        });

        if (!Schema::hasTable('incident_logs')) {
            Schema::rename('update_logs', 'incident_logs');
        }
    }

};
