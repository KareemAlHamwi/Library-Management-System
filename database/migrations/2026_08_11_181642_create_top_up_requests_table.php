<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('top_up_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->nullable();

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
        Schema::dropIfExists('top_up_requests');
    }
};
