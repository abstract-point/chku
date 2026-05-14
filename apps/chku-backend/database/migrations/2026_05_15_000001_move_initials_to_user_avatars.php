<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'avatar_path')) {
                $table->string('avatar_path')->nullable()->after('password');
            }
        });

        Schema::table('club_members', function (Blueprint $table): void {
            if (Schema::hasColumn('club_members', 'initials')) {
                $table->dropColumn('initials');
            }
        });
    }

    public function down(): void
    {
        Schema::table('club_members', function (Blueprint $table): void {
            if (! Schema::hasColumn('club_members', 'initials')) {
                $table->string('initials', 10)->nullable()->after('favorite_genre_id');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'avatar_path')) {
                $table->dropColumn('avatar_path');
            }
        });
    }
};
