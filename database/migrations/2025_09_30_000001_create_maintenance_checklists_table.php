<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('maintenance_checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_line_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('maintenance_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('maintenance_checklist_templates')->cascadeOnDelete();
            $table->string('description');
            $table->boolean('required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('maintenance_checklist_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_id')->constrained('maintenances')->cascadeOnDelete();
            $table->foreignId('checklist_item_id')->constrained('maintenance_checklist_items')->cascadeOnDelete();
            $table->boolean('checked')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['maintenance_id', 'checklist_item_id'], 'maint_check_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('maintenance_checklist_responses');
        Schema::dropIfExists('maintenance_checklist_items');
        Schema::dropIfExists('maintenance_checklist_templates');
    }
};
