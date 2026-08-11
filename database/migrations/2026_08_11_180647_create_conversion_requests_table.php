<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversion_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->integer('points_to_convert');
            $table->integer('loyalty_points_expected');

            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending');

            $table->timestamp('requested_at');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->text('rejection_reason')->nullable();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversion_requests');
    }
};
