<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_private_memos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_record_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->text('body');
            $table->timestamps();
        });

        // Migrate existing single-value pivot memos into the new log as one entry each.
        $rows = DB::table('contact_record_user')
            ->whereNotNull('private_memo')
            ->where('private_memo', '!=', '')
            ->get();
        foreach ($rows as $r) {
            DB::table('contact_private_memos')->insert([
                'contact_record_id' => $r->contact_record_id,
                'user_id' => $r->user_id,
                'body' => $r->private_memo,
                'created_at' => $r->updated_at ?? now(),
                'updated_at' => $r->updated_at ?? now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_private_memos');
    }
};
