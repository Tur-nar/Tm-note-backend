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
        Schema::create('canvas_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shared_with_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('shared_email');
            $table->enum('permission', ['view', 'edit'])->default('view');
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            $table->string('invite_token', 64)->unique();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            // One canvas share per owner per email
            $table->unique(['owner_id', 'shared_email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('canvas_shares');
    }
};
