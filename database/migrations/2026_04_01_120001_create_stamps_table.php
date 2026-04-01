<?php

use App\Models\messageRecord;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stamps', function (Blueprint $table) {
            $table->id();
            $table->morphs('stampable');
            $table->string('emote_name', 50);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['stampable_type', 'stampable_id', 'user_id'], 'stamps_stampable_user_unique');
        });

        if (! Schema::hasTable('message_emote_users')) {
            return;
        }

        DB::table('message_emote_users')
            ->select(['message_record_id', 'user_id', 'emote_name', 'created_at', 'updated_at'])
            ->orderBy('message_record_id')
            ->chunk(1000, function ($rows): void {
                $payload = [];

                foreach ($rows as $row) {
                    $emoteName = trim((string) ($row->emote_name ?? ''));

                    $payload[] = [
                        'stampable_type' => messageRecord::class,
                        'stampable_id' => $row->message_record_id,
                        'emote_name' => $emoteName,
                        'user_id' => $row->user_id,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ];
                }

                if (! empty($payload)) {
                    DB::table('stamps')->upsert(
                        $payload,
                        ['stampable_type', 'stampable_id', 'user_id'],
                        ['emote_name', 'updated_at']
                    );
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('stamps');
    }
};
