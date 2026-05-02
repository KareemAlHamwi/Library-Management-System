<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('book_files', function (Blueprint $table) {
            $table->id();

            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->enum('file_type', ['pdf', 'audio']);
            $table->string('file_url');
            $table->decimal('file_size_mb');
            $table->integer('duration_sec')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_files');
    }
};
