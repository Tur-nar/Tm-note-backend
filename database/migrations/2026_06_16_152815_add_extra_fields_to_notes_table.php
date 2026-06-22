<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            // 'html' = TipTap rich text, 'markdown' = raw markdown
            $table->enum('content_format', ['html', 'markdown'])->default('html')->after('content');

            // Note card color in the sidebar (hex code)
            $table->string('color', 7)->default('#1b1f2c')->after('content_format');

            // Pin important notes to the top of the list
            $table->boolean('is_pinned')->default(false)->after('color');

            // Soft archive — note is hidden from main list but not deleted
            $table->timestamp('archived_at')->nullable()->after('is_pinned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn(['content_format', 'color', 'is_pinned', 'archived_at']);
        });
    }
};
