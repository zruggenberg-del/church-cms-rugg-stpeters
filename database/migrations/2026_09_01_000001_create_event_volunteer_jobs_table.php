<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_volunteer_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('church_id');
            $table->foreign('church_id')->references('id')->on('church')->onDelete('cascade');
            $table->unsignedInteger('event_id');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('slots_needed')->default(1);
            $table->boolean('allow_self_signup')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_volunteer_jobs');
    }
};
