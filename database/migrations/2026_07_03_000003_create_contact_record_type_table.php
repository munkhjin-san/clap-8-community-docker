<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_record_type', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('contact_record_id');
            $table->foreign('contact_record_id')->references('id')->on('contact_records')->cascadeOnDelete();
            // contact_types.id is a signed INT (legacy table) — match it exactly.
            $table->integer('contact_type_id');
            $table->foreign('contact_type_id')->references('id')->on('contact_types')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['contact_record_id', 'contact_type_id']);
        });

        // Backfill the pivot from the existing single contact_type_id so nothing is lost.
        DB::table('contact_records')
            ->whereNotNull('contact_type_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                $now = now();
                $insert = [];
                foreach ($rows as $row) {
                    $insert[] = [
                        'contact_record_id' => $row->id,
                        'contact_type_id' => $row->contact_type_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                if ($insert) {
                    DB::table('contact_record_type')->insertOrIgnore($insert);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_record_type');
    }
};
