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
        Schema::create('price_rules', function (Blueprint $table) {
            // Identificação
            $table->id();
            $table->string('admin_graphql_api_id')->nullable();
            $table->string('title');
            
            // Configuração básica
            $table->string('value_type'); // fixed_amount, percentage
            $table->decimal('value', 10, 2);
            $table->string('customer_selection'); // all, prerequisite
            $table->string('target_type'); // line_item, shipping_line
            $table->string('target_selection'); // all, entitled
            $table->string('allocation_method'); // each, across
            $table->integer('allocation_limit')->nullable();
            $table->boolean('once_per_customer')->default(false);
            $table->integer('usage_limit')->nullable();
            
            // Período de validade
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            
            // Filtros de produtos
            $table->json('entitled_product_ids')->nullable();
            $table->json('entitled_variant_ids')->nullable();
            $table->json('entitled_collection_ids')->nullable();
            $table->json('entitled_country_ids')->nullable();
            
            // Pré-requisitos
            $table->json('prerequisite_product_ids')->nullable();
            $table->json('prerequisite_variant_ids')->nullable();
            $table->json('prerequisite_collection_ids')->nullable();
            $table->json('prerequisite_customer_ids')->nullable();
            $table->json('customer_segment_prerequisite_ids')->nullable();
            
            // Faixas de pré-requisitos
            $table->json('prerequisite_subtotal_range')->nullable();
            $table->json('prerequisite_quantity_range')->nullable();
            $table->json('prerequisite_shipping_price_range')->nullable();
            
            // Relações de quantidade
            $table->json('prerequisite_to_entitlement_quantity_ratio')->nullable();
            $table->json('prerequisite_to_entitlement_purchase')->nullable();
            
            // Timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            
            // Índices
            $table->index('title');
            $table->index('value_type');
            $table->index(['starts_at', 'ends_at']);
            $table->index('customer_selection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_rules');
    }
};
