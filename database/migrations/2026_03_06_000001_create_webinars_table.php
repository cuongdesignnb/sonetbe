<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webinars', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('instructor_name');
            $table->string('instructor_avatar')->nullable();
            $table->string('zoom_link')->nullable();
            $table->string('replay_url')->nullable();
            $table->dateTime('scheduled_at');
            $table->integer('duration_minutes')->nullable();
            $table->integer('views_count')->default(0);
            $table->enum('status', ['upcoming', 'live', 'completed', 'cancelled'])->default('upcoming');
            $table->boolean('is_free')->default(true);
            $table->decimal('price', 10, 2)->default(0);
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'scheduled_at']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webinars');
    }
};
