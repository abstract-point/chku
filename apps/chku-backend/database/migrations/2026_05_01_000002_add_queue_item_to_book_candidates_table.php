<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_candidates', function (Blueprint $table) {
            $table->foreignId('member_book_queue_item_id')
                ->nullable()
                ->after('reading_cycle_id')
                ->constrained('member_book_queue_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('book_candidates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_book_queue_item_id');
        });
    }
};
