<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_records', function (Blueprint $table) {
            $table->string('card_hash', 128)->nullable()->after('card_path')->index();
            $table->boolean('is_duplicate')->default(false)->after('card_hash');
            $table->unsignedBigInteger('duplicate_of')->nullable()->after('is_duplicate')->index();
        });
    }

    public function down(): void
    {
        Schema::table('contact_records', function (Blueprint $table) {
            $table->dropColumn(['card_hash', 'is_duplicate', 'duplicate_of']);
        });
    }
};
