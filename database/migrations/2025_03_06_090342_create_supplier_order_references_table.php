<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplierOrderReferencesTable extends Migration
{
    public function up()
    {
        Schema::create('supplier_order_references', function (Blueprint $table) {
            // Usamos 'id' como PK con tipo string para almacenar el código único (por ejemplo "Z465")
            $table->string('id')->primary();
            $table->string('company_name');
            $table->text('descrip');
            $table->decimal('value', 10, 2);
            $table->string('measure');
            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('supplier_order_references');
    }
}
