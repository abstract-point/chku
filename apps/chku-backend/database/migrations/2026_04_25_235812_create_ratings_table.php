<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reading_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('club_member_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->timestamps();

            $table->unique(['reading_cycle_id', 'club_member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
