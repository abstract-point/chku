<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_members', function (Blueprint $table) {
            $table->unsignedInteger('gold_owls_count')->default(0)->after('favorite_genre_id');
            $table->unsignedInteger('silver_owls_count')->default(0)->after('gold_owls_count');
            $table->unsignedInteger('bronze_owls_count')->default(0)->after('silver_owls_count');
        });
    }

    public function down(): void
    {
        Schema::table('club_members', function (Blueprint $table) {
            $table->dropColumn(['gold_owls_count', 'silver_owls_count', 'bronze_owls_count']);
        });
    }
};
