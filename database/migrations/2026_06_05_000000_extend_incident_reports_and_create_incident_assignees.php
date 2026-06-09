<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_reports', function (Blueprint $table) {
            $table->unsignedInteger('step')->default(1)->after('incident_id')->index();
            $table->text('request')->nullable()->after('report');
            $table->unsignedBigInteger('created_by')->nullable()->after('request')->index();
            $table->timestamp('completed_at')->nullable()->after('created_by')->index();
        });

        Schema::create('incident_assignees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('incident_report_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->text('report')->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['incident_report_id', 'user_id']);
            $table->index(['user_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_assignees');

        Schema::table('incident_reports', function (Blueprint $table) {
            $table->dropColumn([
                'step',
                'request',
                'created_by',
                'completed_at',
            ]);
        });
    }
};
