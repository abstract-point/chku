<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('genre_id')->nullable()->constrained('genres')->nullOnDelete();
            $table->string('title');
            $table->string('author');
            $table->string('slug')->unique();
            $table->string('isbn')->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('cover_color')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
