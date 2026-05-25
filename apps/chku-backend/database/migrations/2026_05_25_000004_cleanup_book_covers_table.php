<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_covers', function (Blueprint $table) {
            $table->dropColumn(['cover_source', 'cover_mime']);
        });
    }

    public function down(): void
    {
        Schema::table('book_covers', function (Blueprint $table) {
            $table->string('cover_mime')->nullable()->after('thumbnail_path');
            $table->string('cover_source')->nullable()->after('cover_size');
        });
    }
};
