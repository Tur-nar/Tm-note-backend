<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            // Spatial positioning for the Infinite Canvas
            $table->float('x_position')->default(0)->after('archived_at');
            $table->float('y_position')->default(0)->after('x_position');
        });
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn(['x_position', 'y_position']);
        });
    }
};
