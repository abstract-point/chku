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
            $table->foreignId('next_turn_order_id')->nullable()->constrained('turn_orders')->nullOnDelete();
            $table->timestamps();

            $table->unique(['club_id', 'club_member_id']);
            $table->unique('next_turn_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turn_orders');
    }
};
