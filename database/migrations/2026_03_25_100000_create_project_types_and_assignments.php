<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->unsignedTinyInteger('status')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('project_records', function (Blueprint $table) {
            $table->foreignId('project_type_id')->nullable()->after('name')->constrained('project_types');
        });

        Schema::table('custom_forms', function (Blueprint $table) {
            $table->foreignId('project_type_id')->nullable()->after('usage')->constrained('project_types');
        });

        $now = now();
        $defaultId = DB::table('project_types')->insertGetId([
            'key' => 'default',
            'label' => '標準',
            'status' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('project_records')
            ->whereNull('project_type_id')
            ->update(['project_type_id' => $defaultId]);

        DB::table('custom_forms')
            ->where('usage', 'project_creation')
            ->whereNull('project_type_id')
            ->update(['project_type_id' => $defaultId]);
    }

    public function down(): void
    {
        Schema::table('custom_forms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_type_id');
        });

        Schema::table('project_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_type_id');
        });

        Schema::dropIfExists('project_types');
    }
};
