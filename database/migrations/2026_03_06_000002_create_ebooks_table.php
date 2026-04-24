<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ebooks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('author_name');
            $table->string('file_url')->nullable();
            $table->string('preview_url')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('original_price', 10, 2)->nullable();
            $table->integer('total_pages')->nullable();
            $table->integer('download_count')->default(0);
            $table->enum('type', ['ebook', 'book', 'guide'])->default('ebook');
            $table->enum('status', ['draft', 'published', 'coming_soon', 'archived'])->default('draft');
            $table->json('features')->nullable();
            $table->json('tags')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->index(['status', 'created_at']);
            $table->index('slug');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ebooks');
    }
};
