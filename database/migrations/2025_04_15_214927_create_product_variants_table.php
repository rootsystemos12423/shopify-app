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
        Schema::create('product_variants', function (Blueprint $table) {
            // Alterado para unsignedBigInteger para compatibilidade
            $table->unsignedBigInteger('id')->primary();  // Em vez de $table->id()
            
            $table->unsignedBigInteger('product_id');
            
            // Variant details
            $table->string('title');
            $table->decimal('price', 12, 2);
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->integer('position');
            
            // Inventory
            $table->string('inventory_policy'); // deny, continue
            $table->string('inventory_management')->nullable(); // shopify, null
            $table->integer('inventory_quantity')->default(0);
            
            // Shipping
            $table->boolean('requires_shipping')->default(true);
            $table->boolean('taxable')->default(true);
            $table->decimal('grams', 10, 2)->default(0);
            $table->decimal('weight', 10, 2)->default(0);
            $table->string('weight_unit')->default('lb');
            
            // Options
            $table->string('option1')->nullable();
            $table->string('option2')->nullable();
            $table->string('option3')->nullable();
            
            // Dates
            $table->timestamps();
            
            // Indexes
            $table->index('price');
            $table->index('sku');
            
            // Foreign key constraint separada para melhor controle
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
