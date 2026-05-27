<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vault_files', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('token')->unique();
            $table->foreignUuid('uploaded_by')->nullable()->constrained('identity_users');
            $table->string('original_name', 255);
            $table->string('storage_path', 512);
            $table->string('mime_type', 127);
            $table->unsignedBigInteger('file_size');
            $table->string('password_hash', 255)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('thumbnail_path', 512)->nullable();
            $table->boolean('is_one_time')->default(false);
            $table->timestampTz('expires_at')->nullable()->index();
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }
};
