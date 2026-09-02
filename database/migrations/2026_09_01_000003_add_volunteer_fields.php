<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('userprofiles', function (Blueprint $table) {
            $table->boolean('can_volunteer_self_signup')->default(true)->after('status');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->boolean('enable_volunteer_signup')->default(true)->after('attendance_group_id');
            $table->json('volunteer_reminder_intervals')->nullable()->after('enable_volunteer_signup');
        });

        DB::table('userprofiles')
            ->where('membership_type', 'guest')
            ->update(['can_volunteer_self_signup' => false]);

        DB::table('userprofiles')
            ->where(function ($query) {
                $query->where('membership_type', 'member')
                    ->orWhereNull('membership_type');
            })
            ->update(['can_volunteer_self_signup' => true]);
    }

    public function down(): void
    {
        Schema::table('userprofiles', function (Blueprint $table) {
            $table->dropColumn('can_volunteer_self_signup');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['enable_volunteer_signup', 'volunteer_reminder_intervals']);
        });
    }
};
