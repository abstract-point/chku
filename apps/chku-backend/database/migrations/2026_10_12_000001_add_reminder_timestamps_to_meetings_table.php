<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->timestamp('rsvp_reminder_sent_at')->nullable();
            $table->timestamp('rating_reminder_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['rsvp_reminder_sent_at', 'rating_reminder_sent_at']);
        });
    }
};
