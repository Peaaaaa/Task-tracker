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
        Schema::create('focus_sessions', function (Blueprint $table) {
    $table->id();

    // Associated task (optional)
    $table->foreignId('task_id')
        ->nullable()
        ->constrained()
        ->nullOnDelete();

    // Session duration in minutes
    $table->unsignedInteger('duration_minutes')->default(25);

    // Focus or break session
    $table->enum('session_type', ['focus', 'break'])
        ->default('focus');

    // Completion status
    $table->boolean('is_completed')->default(false);

    // Session start and completion times
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('focus_sessions');
    }
};
