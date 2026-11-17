<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_attachments', function (Blueprint $table) {
            $table->string('cloudinary_public_id')->nullable()->unique();
            $table->text('cloudinary_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('task_attachments', function (Blueprint $table) {
            $table->dropUnique(['cloudinary_public_id']);
            $table->dropColumn([
                'cloudinary_public_id',
                'cloudinary_url',
            ]);
        });
    }
};
