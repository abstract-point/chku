<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_candidate_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('club_member_id')->constrained()->cascadeOnDelete();
            $table->string('response');
            $table->timestamps();

            $table->unique(['book_candidate_id', 'club_member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_candidate_responses');
    }
};
