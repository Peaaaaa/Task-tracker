
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('task_attachments', 'cloudinary_public_id')) {
            Schema::table('task_attachments', function (Blueprint $table) {
                $table->string('cloudinary_public_id')->nullable()->unique();
            });
        }

        if (!Schema::hasColumn('task_attachments', 'cloudinary_url')) {
            Schema::table('task_attachments', function (Blueprint $table) {
                $table->text('cloudinary_url')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('task_attachments', 'cloudinary_url')) {
            Schema::table('task_attachments', function (Blueprint $table) {
                $table->dropColumn('cloudinary_url');
            });
        }

        if (Schema::hasColumn('task_attachments', 'cloudinary_public_id')) {
            Schema::table('task_attachments', function (Blueprint $table) {
                $table->dropUnique(['cloudinary_public_id']);
                $table->dropColumn('cloudinary_public_id');
            });
        }
    }
};
