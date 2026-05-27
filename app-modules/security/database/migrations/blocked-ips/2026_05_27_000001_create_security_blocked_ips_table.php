<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_blocked_ips', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('ip', 45)->unique(); // supports IPv4 and IPv6
            $table->text('reason');
            $table->timestampTz('blocked_until')->nullable(); // null = permanent block
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_blocked_ips');
    }
};
