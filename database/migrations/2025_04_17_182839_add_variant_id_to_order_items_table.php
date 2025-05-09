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
            // Adiciona a coluna variant_id como unsignedBigInteger
            $table->unsignedBigInteger('variant_id')
                  ->after('product_id')
                  ->nullable(); // Pode ser nullable se um item não tiver variante

            // Adiciona a chave estrangeira para product_variants
            $table->foreign('variant_id')
                  ->references('id')
                  ->on('product_variants')
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
            $table->dropForeign(['variant_id']);
            
            // Remove a coluna
            $table->dropColumn('variant_id');
        });
    }
};
