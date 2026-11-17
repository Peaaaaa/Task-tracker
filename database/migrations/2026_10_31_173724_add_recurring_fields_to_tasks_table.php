<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_frequency')->nullable();
            $table->unsignedInteger('recurrence_interval')->default(1);
            $table->dateTime('recurrence_end_date')->nullable();
            $table->dateTime('next_recurrence_date')->nullable();
            $table->unsignedBigInteger('recurrence_parent_id')->nullable();

            $table->foreign('recurrence_parent_id')
                ->references('id')
                ->on('tasks')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['recurrence_parent_id']);
            $table->dropColumn([
                'is_recurring',
                'recurrence_frequency',
                'recurrence_interval',
                'recurrence_end_date',
                'next_recurrence_date',
                'recurrence_parent_id',
            ]);
        });
    }
};