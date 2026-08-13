<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Temporary, time-limited access to a course/script for a demo user,
     * granted without going through the purchase flow. Table name is
     * explicitly "demo_access" (not the auto-pluralized "demo_accesses")
     * to match docs/PROJECT_PLAN.md.
     */
    public function up(): void
    {
        Schema::create('demo_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('resource');
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_access');
    }
};
