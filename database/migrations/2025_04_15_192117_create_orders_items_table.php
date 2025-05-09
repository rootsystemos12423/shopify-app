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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            
            $table->string('title');
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('grams', 10, 2);
            $table->string('sku')->nullable();
            $table->string('variant_title')->nullable();
            $table->string('fulfillment_status')->nullable();
            $table->boolean('requires_shipping')->default(true);
            $table->boolean('taxable')->default(true);
            $table->boolean('gift_card')->default(false);
            
            // Campos específicos do Shopify
            $table->json('properties')->nullable();
            $table->json('tax_lines')->nullable();
            $table->json('discount_allocations')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders_items');
    }
};
