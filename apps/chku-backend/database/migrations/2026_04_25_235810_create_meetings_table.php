<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reading_cycle_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('date');
            $table->time('time');
            $table->string('place');
            $table->string('address')->nullable();
            $table->string('reservation')->nullable();
            $table->string('link')->nullable();
            $table->json('topics')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
