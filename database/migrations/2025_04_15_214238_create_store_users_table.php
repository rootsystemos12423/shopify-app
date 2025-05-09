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
        Schema::create('store_users', function (Blueprint $table) {
            $table->id();
            $table->string('admin_graphql_api_id')->nullable();
            
            // Informações básicas
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('url')->nullable();
            $table->string('im')->nullable(); // Instant messaging
            $table->string('screen_name')->nullable();
            $table->string('phone')->nullable();
            $table->text('bio')->nullable();
            
            // Configurações de conta
            $table->boolean('account_owner')->default(false);
            $table->boolean('receive_announcements')->default(false);
            $table->string('locale', 10)->default('en');
            $table->string('user_type')->default('regular'); // regular, restricted, etc
            $table->boolean('tfa_enabled')->default(false);
            
            // Permissões (armazenadas como JSON)
            $table->json('permissions')->nullable();
            
            // Timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            
            // Índices
            $table->index('email');
            $table->index('account_owner');
            $table->index('user_type');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_users');
    }
};
