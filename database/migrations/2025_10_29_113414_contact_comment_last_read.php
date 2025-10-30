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
        Schema::create('contact_comment_last_reads', function (BluePrint $t) {
            $t->id();
            $t->unsignedBigInteger('contact_record_id')->index();
            $t->unsignedBigInteger('user_id')->index();
            $t->timestamp('last_read_at')->nullable();

            $t->unique(['contact_record_id', 'user_id']);
            $t->timestamps();
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
