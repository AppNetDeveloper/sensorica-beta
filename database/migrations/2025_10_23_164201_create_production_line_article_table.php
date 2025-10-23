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
        Schema::create('production_line_article', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_line_id');
            $table->unsignedBigInteger('article_id');
            $table->unsignedInteger('order')->nullable();
            $table->timestamps();

            $table->foreign('production_line_id')->references('id')->on('production_lines')->onDelete('cascade');
            $table->foreign('article_id')->references('id')->on('articles')->onDelete('cascade');
            $table->unique(['production_line_id', 'article_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_line_article');
    }
};