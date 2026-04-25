<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reading_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('club_member_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->unsignedTinyInteger('progress_percent')->nullable();
            $table->unsignedInteger('current_page')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['reading_cycle_id', 'club_member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_progress');
    }
};
