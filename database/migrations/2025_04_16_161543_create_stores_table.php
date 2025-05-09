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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            // Relacionamento com o usuário (tabela users padrão do Laravel)
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');
            
            
            // Dados da loja
            $table->string('name'); // Nome da loja
            
            // Status e configurações
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // Configurações adicionais em JSON
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes(); // Para deletar de forma reversível
            
            $table->string('personal_token', 64)
                  ->nullable()
                  ->unique();
            // Índices
            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
