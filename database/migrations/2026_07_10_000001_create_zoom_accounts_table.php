<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zoom_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('slot')->unique();
            $table->string('label');
            $table->string('host_email')->nullable();
            $table->text('host_password')->nullable();
            $table->string('account_id')->nullable();
            $table->string('client_id')->nullable();
            $table->text('client_secret')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->boolean('active')->default(false);
            $table->timestamps();
        });

        DB::table('zoom_accounts')->insert([
            [
                'slot' => 0,
                'label' => 'Zoom1',
                'host_email' => 'zoom1@glowd.co.jp',
                'account_id' => 'NIv1ZAkIRdCnjX7uddQLAQ',
                'client_id' => 'fwZ711P6R5C47R5pb4zugg',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot' => 1,
                'label' => 'Zoom2',
                'host_email' => 'zoom2@glowd.co.jp',
                'account_id' => 'NIv1ZAkIRdCnjX7uddQLAQ',
                'client_id' => 'ozsT8JcdQpKLhdbdVPMZzg',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot' => 2,
                'label' => 'Zoom3',
                'host_email' => 'zoom3@glowd.co.jp',
                'account_id' => 'NIv1ZAkIRdCnjX7uddQLAQ',
                'client_id' => '62FVOmH6SZu1rVpPxiZaFw',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('zoom_accounts');
    }
};
