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
        Schema::create('collections', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('store_id');
            $table->string('title');
            $table->string('handle');
            $table->text('body_html')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('sort_order')->nullable(); // 'best-selling', 'manual', etc.
            $table->string('template_suffix')->nullable();
            $table->boolean('disjunctive')->default(false); // true/false for rule combinations
            $table->json('rules')->nullable(); // stores the rules array
            $table->string('published_scope')->nullable(); // 'web' or 'global'
            $table->integer('products_count')->default(0);
            $table->json('image')->nullable();
            $table->json('shopify_data')->nullable(); // raw Shopify API response
            $table->timestamps();
        
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
