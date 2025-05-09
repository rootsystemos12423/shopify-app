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
        Schema::table('theme_assets', function (Blueprint $table) {
            // Verifica se a coluna já existe antes de adicionar
            if (!Schema::hasColumn('theme_assets', 'theme_id')) {
                $table->foreignId('theme_id')
                    ->after('id')
                    ->constrained()
                    ->onDelete('cascade');
                
                // Adiciona índice após criar a coluna
                $table->index('theme_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('theme_assets', function (Blueprint $table) {
            // Remove a chave estrangeira primeiro
            $table->dropForeign(['theme_id']);
            // Depois remove a coluna
            $table->dropColumn('theme_id');
        });
    }
};
