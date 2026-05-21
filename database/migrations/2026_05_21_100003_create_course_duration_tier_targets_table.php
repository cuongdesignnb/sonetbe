<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_duration_tier_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duration_tier_id')->constrained('course_duration_tiers')->onDelete('cascade');
            $table->enum('target_type', ['user', 'group']);
            $table->unsignedBigInteger('target_id');
            $table->timestamps();

            $table->unique(['duration_tier_id', 'target_type', 'target_id'], 'tier_target_unique');
            $table->index(['target_type', 'target_id'], 'tier_target_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_duration_tier_targets');
    }
};
