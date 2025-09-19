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
        Schema::create('ia_prompt_executions', function (Blueprint $table) {
            $table->id();

            // Contexto del cliente y prompt
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('prompt_key'); // Ej: process_group.cnc
            $table->string('category');   // Ej: process_group
            $table->string('subcategory'); // Ej: CNC
            $table->string('model_name')->nullable();
            $table->string('ai_provider')->nullable();
            $table->string('ai_url_used')->nullable();

            // Payloads
            $table->json('variables_json');
            $table->longText('prompt_text');
            $table->json('response_json')->nullable();
            $table->longText('response_text')->nullable();

            // Estado y cola externa
            $table->string('tasker_id')->nullable()->index();
            $table->string('status')->default('queued')->index(); // queued|running|success|error|expired
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();

            // Control de reintentos / polling
            $table->unsignedInteger('retry_count')->default(0);
            $table->unsignedInteger('max_retries')->default(10);
            $table->timestamp('last_polled_at')->nullable();
            $table->timestamp('next_poll_at')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            // Auditoría
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['category', 'subcategory']);
            $table->index(['prompt_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ia_prompt_executions');
    }
};
