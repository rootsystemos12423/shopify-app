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
        // database/migrations/tenant/xxxx_create_orders_table.php
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('admin_graphql_api_id')->nullable();
            $table->string('order_number')->unique();            
            // Informações básicas
            $table->string('email')->nullable();
            $table->boolean('buyer_accepts_marketing')->default(false);
            $table->string('currency', 3);
            $table->decimal('current_subtotal_price', 10, 2);
            $table->decimal('current_total_discounts', 10, 2);
            $table->decimal('current_total_price', 10, 2);
            $table->decimal('current_total_tax', 10, 2);
            $table->string('financial_status'); // paid, pending, refunded, etc
            $table->string('fulfillment_status')->nullable();
            $table->string('landing_site')->nullable();
            $table->string('name'); // #1001
            $table->string('phone')->nullable();
            $table->string('presentment_currency', 3);
            $table->timestamp('processed_at');
            $table->string('source_name'); // web, pos, etc
            $table->decimal('subtotal_price', 10, 2);
            $table->decimal('total_discounts', 10, 2);
            $table->decimal('total_line_items_price', 10, 2);
            $table->decimal('total_outstanding', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->decimal('total_tax', 10, 2);
            $table->decimal('total_tip_received', 10, 2)->default(0);
            $table->integer('total_weight')->default(0);
            $table->boolean('taxes_included')->default(false);
            $table->boolean('test')->default(false);
            $table->string('token')->unique();
            
            // Endereços (armazenados como JSON)
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            
            // Campos adicionais
            $table->json('client_details')->nullable();
            $table->json('discount_codes')->nullable();
            $table->json('note_attributes')->nullable();
            $table->string('tags')->nullable();
            $table->json('payment_gateway_names')->nullable();
            
            // Timestamps
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
