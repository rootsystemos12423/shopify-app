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
        Schema::table('store_users', function (Blueprint $table) {
            // Adiciona store_id como chave estrangeira
            $table->unsignedBigInteger('store_id')
                  ->after('id')
                  ->nullable(); // Temporariamente nullable para permitir a migração

            // Adiciona colunas restantes conforme necessário
            $table->string('timezone')->nullable()
                  ->after('locale');
            $table->string('currency', 3)->nullable()
                  ->after('timezone');
            $table->boolean('has_access_all_locations')->default(false)
                  ->after('tfa_enabled');

            // Adiciona chave estrangeira para store_id
            $table->foreign('store_id')
                  ->references('id')
                  ->on('stores')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_users', function (Blueprint $table) {
            // Remove a chave estrangeira primeiro
            $table->dropForeign(['store_id']);
            
            // Remove as colunas adicionadas
            $table->dropColumn([
                'store_id',
                'timezone',
                'currency',
                'has_access_all_locations'
            ]);
        });
    }
};
