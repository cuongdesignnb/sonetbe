<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('video_url')->nullable();
            $table->string('video_bunny_id')->nullable();
            $table->string('video_local_path')->nullable();
            $table->integer('duration')->nullable(); // in seconds
            $table->integer('order')->default(1);
            $table->boolean('is_preview')->default(false);
            $table->longText('content')->nullable();
            $table->json('resources')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            $table->index(['course_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};