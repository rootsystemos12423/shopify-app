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
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('shopify_theme_id')->nullable()->comment('ID do tema na Shopify');
            $table->string('name');
            $table->string('role')->nullable()->comment('main, development, etc');
            $table->boolean('active')->default(false);
            $table->string('version')->default('1.0.0');
            $table->string('current_version_name')->default('v1');
            $table->json('settings')->nullable()->comment('Configurações do tema');
            $table->json('shopify_data')->nullable()->comment('Dados completos da API Shopify');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'active']);
        });

        Schema::create('theme_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained()->onDelete('cascade');
            $table->string('version_name')->comment('v1, v2, etc');
            $table->string('version_number')->comment('SemVer: 1.0.0');
            $table->json('assets_manifest')->nullable()->comment('Lista de arquivos com hashes');
            $table->text('notes')->nullable()->comment('Notas da versão');
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->unique(['theme_id', 'version_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('theme_versions');
        Schema::dropIfExists('themes');
    }
};
