<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webinars', function (Blueprint $table) {
            $table->string('replay_bunny_id', 100)->nullable()->after('replay_url');
            $table->string('replay_bunny_library_id', 50)->nullable()->after('replay_bunny_id');
        });
    }

    public function down(): void
    {
        Schema::table('webinars', function (Blueprint $table) {
            $table->dropColumn(['replay_bunny_id', 'replay_bunny_library_id']);
        });
    }
};
