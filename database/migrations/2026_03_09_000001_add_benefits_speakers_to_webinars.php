<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webinars', function (Blueprint $table) {
            $table->json('benefits')->nullable()->after('tags');
            $table->json('speakers')->nullable()->after('benefits');
            $table->integer('max_attendees')->nullable()->after('speakers');
        });
    }

    public function down(): void
    {
        Schema::table('webinars', function (Blueprint $table) {
            $table->dropColumn(['benefits', 'speakers', 'max_attendees']);
        });
    }
};
