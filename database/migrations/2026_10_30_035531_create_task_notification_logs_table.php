
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_notification_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('push_subscription_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('notification_type');
            $table->date('notified_for');
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            $table->unique([
                'task_id',
                'push_subscription_id',
                'notification_type',
                'notified_for',
            ], 'task_notification_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_notification_logs');
    }
};
