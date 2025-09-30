<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('variance_alert_logs', function (Blueprint $t) {
            $t->id();
            $t->integer('project_record_id');
            $t->date('period');
            $t->string('hash', 64);
            $t->timestamps();
            $t->timestamp('sent_at')->nullable();
            $t->unique(['project_record_id', 'period']);
            $t->index('hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
