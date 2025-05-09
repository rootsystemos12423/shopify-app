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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('admin_graphql_api_id')->nullable();
            
            // Informações básicas
            $table->string('title');
            $table->string('handle')->unique(); // URL slug
            $table->text('body_html')->nullable();
            $table->string('author');
            $table->string('template_suffix')->nullable();
            
            // Status de publicação
            $table->timestamp('published_at')->nullable();
            
            // Timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            
            // Índices
            $table->index('handle');
            $table->index('author');
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
