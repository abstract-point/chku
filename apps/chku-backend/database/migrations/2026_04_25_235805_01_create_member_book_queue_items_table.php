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
            $table->unsignedSmallInteger('position');
            $table->text('reason')->nullable();
            $table->text('description')->nullable();
            $table->string('status');
            $table->timestamps();

            $table->unique(['club_member_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_book_queue_items');
    }
};
