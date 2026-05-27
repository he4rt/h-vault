<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vault_files', function (Blueprint $table): void {
            $table->foreignUuid('bucket_id')->nullable()->after('token')->constrained('vault_buckets')->nullOnDelete();
            $table->foreignUuid('album_id')->nullable()->after('bucket_id')->constrained('vault_albums')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vault_files', function (Blueprint $table): void {
            $table->dropConstrainedForeignUuid('album_id');
            $table->dropConstrainedForeignUuid('bucket_id');
        });
    }
};
