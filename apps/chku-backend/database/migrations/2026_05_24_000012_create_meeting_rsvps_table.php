<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_rsvps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('club_member_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->timestamps();

            $table->unique(['meeting_id', 'club_member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_rsvps');
    }
};
