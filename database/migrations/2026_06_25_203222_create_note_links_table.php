<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('note_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_note_id')->constrained('notes')->cascadeOnDelete();
            $table->foreignId('target_note_id')->constrained('notes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            // Prevent duplicate links between the same pair
            $table->unique(['source_note_id', 'target_note_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('note_links');
    }
};
