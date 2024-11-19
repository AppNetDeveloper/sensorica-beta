<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductListsTable extends Migration
{
    public function up(): void
    {
        Schema::create('product_lists', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('client_id'); // Foreign key for the client
            $table->string('name'); // Product name
            $table->timestamps(); // created_at and updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_lists');
    }
}
