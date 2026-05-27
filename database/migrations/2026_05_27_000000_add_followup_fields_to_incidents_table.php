<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->text('prevention_apply_status')->nullable()->after('prevention');
            $table->text('payee')->nullable()->after('amount_of_damage');
            $table->text('expense_details')->nullable()->after('payee');
            $table->text('aftermath_comment')->nullable()->after('memo');
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn([
                'prevention_apply_status',
                'payee',
                'expense_details',
                'aftermath_comment',
            ]);
        });
    }
};
