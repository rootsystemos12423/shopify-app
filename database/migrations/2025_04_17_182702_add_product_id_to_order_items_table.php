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
        Schema::table('order_items', function (Blueprint $table) {
            // Adiciona a coluna product_id como unsignedBigInteger (para relacionamento com products)
            $table->unsignedBigInteger('product_id')
                  ->after('order_id')
                  ->nullable(); // Pode ser nullable temporariamente

            // Adiciona a chave estrangeira
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('set null'); // Ou 'cascade' conforme sua necessidade
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Remove a chave estrangeira primeiro
            $table->dropForeign(['product_id']);
            
            // Remove a coluna
            $table->dropColumn('product_id');
        });
    }
};
