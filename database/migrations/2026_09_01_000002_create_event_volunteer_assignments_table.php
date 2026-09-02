<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_volunteer_assignments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('event_volunteer_job_id');
            $table->foreign('event_volunteer_job_id')->references('id')->on('event_volunteer_jobs')->onDelete('cascade');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->enum('status', ['confirmed', 'pending', 'declined', 'cancelled'])->default('confirmed');
            $table->unsignedInteger('assigned_by')->nullable();
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['event_volunteer_job_id', 'user_id'], 'ev_assign_job_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_volunteer_assignments');
    }
};
