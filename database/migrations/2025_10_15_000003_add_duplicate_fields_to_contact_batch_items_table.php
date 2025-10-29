<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_batch_items', function (Blueprint $table) {
            $table->string('card_hash', 128)->nullable()->after('stored_path')->index();
            $table->json('duplicate_candidates')->nullable()->after('card_hash');
            $table->boolean('needs_review')->default(false)->after('duplicate_candidates');
        });
    }

    public function down(): void
    {
        Schema::table('contact_batch_items', function (Blueprint $table) {
            $table->dropColumn(['card_hash', 'duplicate_candidates', 'needs_review']);
        });
    }
};
