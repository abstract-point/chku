<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_book_queue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('next_queue_item_id')
                ->nullable()
                ->constrained('member_book_queue_items')
                ->nullOnDelete();
            $table->text('reason')->nullable();
            $table->text('description')->nullable();
            $table->string('status');
            $table->timestamps();

            $table->unique('next_queue_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_book_queue_items');
    }
};
