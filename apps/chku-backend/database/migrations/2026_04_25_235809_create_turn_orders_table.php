<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turn_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('club_member_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->boolean('is_current')->default(false);
            $table->boolean('is_next')->default(false);
            $table->timestamp('skipped_at')->nullable();
            $table->timestamps();

            $table->unique(['club_id', 'club_member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turn_orders');
    }
};
