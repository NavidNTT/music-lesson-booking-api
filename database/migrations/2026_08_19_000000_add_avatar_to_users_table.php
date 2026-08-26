<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('role');
        });

        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->string('certificate_path')->nullable()->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropColumn('certificate_path');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_path');
        });
    }
};