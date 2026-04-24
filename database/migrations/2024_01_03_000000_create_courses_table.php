<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('thumbnail')->nullable();
            $table->string('preview_video')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('instructor_id');
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->integer('duration')->nullable(); // in minutes
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->text('meta_description')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('category_id')->references('id')->on('categories');
            $table->foreign('instructor_id')->references('id')->on('users');
            
            $table->index(['status', 'created_at']);
            $table->index('category_id');
            $table->index('instructor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};