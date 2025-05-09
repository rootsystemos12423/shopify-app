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
        Schema::create('shopify_integrations', function (Blueprint $table) {
            $table->id();
            
            // Relacionamento com a loja/store
            $table->unsignedBigInteger('store_id');
            
            // Dados principais da integração
            $table->string('shopify_domain'); // nomedaloja (sem .myshopify.com)
            $table->string('api_key');
            $table->string('api_secret');
            $table->string('admin_token');
            $table->string('webhook_secret')->nullable();
            
            // Metadados e status
            $table->datetime('last_sync_at')->nullable();
            $table->text('sync_errors')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Chaves estrangeiras e índices
            $table->foreign('store_id')
                  ->references('id')
                  ->on('stores')
                  ->onDelete('cascade');
                  
            $table->index('shopify_domain');
            $table->index('store_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopify');
    }
};
