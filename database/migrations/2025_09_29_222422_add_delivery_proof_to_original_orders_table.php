<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryProofToOriginalOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('original_orders', function (Blueprint $table) {
            $table->text('delivery_signature')->nullable()->after('actual_delivery_date')->comment('Firma digital del cliente en base64');
            $table->json('delivery_photos')->nullable()->after('delivery_signature')->comment('Array de rutas de fotos de entrega');
            $table->text('delivery_notes')->nullable()->after('delivery_photos')->comment('Notas del transportista sobre la entrega');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('original_orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_signature', 'delivery_photos', 'delivery_notes']);
        });
    }
}
