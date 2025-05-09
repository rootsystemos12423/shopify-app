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
        Schema::create('script_tags', function (Blueprint $table) {
            $table->id();
            
            // Configurações do script
            $table->string('src'); // URL do script
            $table->string('event'); // onload, etc
            $table->string('display_scope'); // all, order_status, etc
            $table->boolean('cache')->default(false);
            
            // Metadados
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            
            // Índices
            $table->index('src');
            $table->index('event');
            $table->index('display_scope');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('script_tags');
    }
};
