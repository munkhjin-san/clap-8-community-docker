<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_record_project', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('contact_record_id');
            $table->foreign('contact_record_id')->references('id')->on('contact_records')->cascadeOnDelete();
            // project_records.id is a signed INT (legacy) — match it.
            $table->integer('project_id');
            $table->foreign('project_id')->references('id')->on('project_records')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['contact_record_id', 'project_id']);
        });

        Schema::create('contact_record_related', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('contact_record_id');
            $table->bigInteger('related_contact_record_id');
            $table->foreign('contact_record_id', 'crr_contact_fk')->references('id')->on('contact_records')->cascadeOnDelete();
            $table->foreign('related_contact_record_id', 'crr_related_fk')->references('id')->on('contact_records')->cascadeOnDelete();
            $table->timestamps();

            // Short explicit name — the auto-generated one exceeds MySQL's 64-char limit.
            $table->unique(['contact_record_id', 'related_contact_record_id'], 'crr_pair_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_record_related');
        Schema::dropIfExists('contact_record_project');
    }
};
