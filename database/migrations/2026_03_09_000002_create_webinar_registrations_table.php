<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webinar_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webinar_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['registered', 'attended', 'cancelled'])->default('registered');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['webinar_id', 'user_id']);
            $table->index(['webinar_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webinar_registrations');
    }
};
