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
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('reported_by')->nullable()->index();
            $table->unsignedBigInteger('caused_by')->nullable()->index();
            $table->unsignedBigInteger('incident_category_id')->nullable()->index();
            $table->unsignedBigInteger('incident_punishment_id')->nullable()->index();
            $table->text('reason')->nullable();
            $table->text('prevention')->nullable();
            $table->text('instruction')->nullable();
            $table->text('resolution')->nullable();
            $table->text('occured_location')->nullable();
            $table->text('memo')->nullable();
            $table->date('occurred_date')->nullable()->index();
            $table->date('instruction_date')->nullable()->index();
            $table->string('related_parties')->nullable();
            $table->unsignedBigInteger('project_record_id')->nullable()->index();
            $table->string('status')->nullable()->index();
            $table->float('amount_of_damage')->nullable();
            $table->integer('risk_level')->nullable();
            $table->integer('severity_level')->nullable();
            $table->text('private_notes')->nullable();
            $table->text('committee_members')->nullable();
            $table->text('committee_decision')->nullable();
            $table->date('committee_decision_date')->nullable();
            $table->softDeletes();
            $table->timestamps();


            $table->index(['project_record_id', 'status']);
            $table->index(['reported_by', 'status']);
            $table->index(['project_record_id', 'reported_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
