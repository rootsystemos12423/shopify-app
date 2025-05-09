<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('theme_assets', function (Blueprint $table) {
            $table->id();
            
            // Identificação do arquivo
            $table->string('key'); // Caminho do arquivo (ex: layout/theme.liquid)
            $table->string('content_type'); // MIME type (ex: application/x-liquid)
            
            // Metadados
            $table->integer('size')->default(0); // Tamanho em bytes
            $table->string('checksum')->nullable(); // Hash para verificação de alterações
            $table->string('public_url')->nullable(); // URL pública (se aplicável)
            
            // Conteúdo (armazenamento flexível)
            $table->text('content')->nullable(); // Para arquivos pequenos
            $table->string('storage_path')->nullable(); // Para arquivos grandes (caminho no filesystem)
            
            // Timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            
            // Índices
            $table->index('key');
            $table->index('content_type');
            

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_assets');
    }
};
