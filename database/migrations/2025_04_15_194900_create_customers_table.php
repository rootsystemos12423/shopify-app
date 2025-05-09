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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('admin_graphql_api_id')->nullable();
            
            // Informações básicas
            $table->string('email')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->integer('orders_count')->default(0);
            $table->string('state'); // disabled, enabled, etc
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->unsignedBigInteger('last_order_id')->nullable();
            $table->string('last_order_name')->nullable();
            $table->text('note')->nullable();
            $table->boolean('verified_email')->default(false);
            $table->string('multipass_identifier')->nullable();
            $table->boolean('tax_exempt')->default(false);
            $table->string('tags')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('phone')->nullable();
            
            // Endereços (armazenados como JSON)
            $table->json('addresses')->nullable();
            $table->json('default_address')->nullable();
            
            // Consentimentos
            $table->json('email_marketing_consent')->nullable();
            $table->json('sms_marketing_consent')->nullable();
            
            // Isenções fiscais
            $table->json('tax_exemptions')->nullable();
            
            // Timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            
            // Índices
            $table->index('email');
            $table->index('state');
            $table->index('total_spent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
