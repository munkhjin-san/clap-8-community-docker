<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_resource_comments', function (Blueprint $t) {
            $t->id();
            $t->string('member_name')->index();
            $t->unsignedBigInteger('user_id')->index();
            $t->text('comment')->nullable();
            $t->string('period', 7)->index();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_resource_comments');
    }
};
