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
        Schema::create('books', function (Blueprint $table) {
            $table->id();

            $table->string('title', 30);
            $table->string('description', 200)->nullable();
            $table->string('cover_image')->nullable();
            $table->integer('total_copies');
            $table->integer('available_copies');
            $table->integer('total_stock_copies');
            $table->integer('available_stock_copies');
            $table->decimal('price')->default(0);
            $table->date('published_year')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
