<?php

use App\Services\Community\CommunityScopeCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ROLE_SCOPE_DEFAULTS = [
        'admin' => [
            'app.project',
            'app.schedule',
            'app.timesheet',
            'app.learning',
            'app.contact',
            'app.notice',
            'app.asset',
            'app.support',
            'app.form',
            'position.management_hq',
            'position.system_development',
            'position.hr',
            'position.pm',
            'position.board',
        ],
        'board' => ['app.project', 'app.schedule', 'app.timesheet', 'app.notice', 'position.board'],
        'pm' => ['app.project', 'app.schedule', 'app.timesheet', 'position.pm'],
        'member' => ['app.schedule', 'app.timesheet', 'app.learning', 'app.contact', 'app.notice', 'app.asset', 'app.support', 'app.form'],
        'registered' => ['app.learning'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('community_roles')) {
            return;
        }

        if (!Schema::hasColumn('community_roles', 'scopes')) {
            Schema::table('community_roles', function (Blueprint $table) {
                $table->json('scopes')->nullable()->after('capabilities');
            });
        }

        DB::table('community_roles')
            ->select(['id', 'key', 'scopes'])
            ->orderBy('id')
            ->chunkById(500, function ($roles) {
                foreach ($roles as $role) {
                    if ($role->scopes !== null) {
                        continue;
                    }

                    $scopes = self::ROLE_SCOPE_DEFAULTS[$role->key] ?? [];
                    if ($role->key === 'admin') {
                        $scopes = CommunityScopeCatalog::allScopes();
                    }

                    DB::table('community_roles')
                        ->where('id', $role->id)
                        ->update([
                            'scopes' => json_encode($scopes, JSON_UNESCAPED_UNICODE),
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('community_roles') && Schema::hasColumn('community_roles', 'scopes')) {
            Schema::table('community_roles', function (Blueprint $table) {
                $table->dropColumn('scopes');
            });
        }
    }
};
