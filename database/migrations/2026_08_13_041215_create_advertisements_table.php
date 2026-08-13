<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An ad a Creator/seller submits for approval before it can run.
     * Reuses the same draft/pending/approved/rejected lifecycle as
     * courses/scripts (ContentStatus) and the same generic `approvals`
     * queue table — this is the second consumer it was built for.
     */
    public function up(): void
    {
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('banner_path')->nullable();
            $table->string('target_url');
            $table->string('status')->default('draft');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
