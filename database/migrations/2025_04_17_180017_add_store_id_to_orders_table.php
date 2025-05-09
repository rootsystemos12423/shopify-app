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
        Schema::table('orders', function (Blueprint $table) {
            // Add the store_id column (unsignedBigInteger to match stores.id)
            $table->unsignedBigInteger('store_id')
                  ->after('id')
                  ->nullable(); // Temporary nullable for existing records

            // Add foreign key constraint
            $table->foreign('store_id')
                  ->references('id')
                  ->on('stores')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['store_id']);
            
            // Then drop the column
            $table->dropColumn('store_id');
        });
    }
};
