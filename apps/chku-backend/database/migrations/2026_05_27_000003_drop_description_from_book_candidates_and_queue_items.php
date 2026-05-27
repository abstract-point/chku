<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_candidates', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('member_book_queue_items', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }

    public function down(): void
    {
        Schema::table('book_candidates', function (Blueprint $table) {
            $table->text('description')->nullable();
        });

        Schema::table('member_book_queue_items', function (Blueprint $table) {
            $table->text('description')->nullable();
        });
    }
};
