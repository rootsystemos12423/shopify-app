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
        Schema::table('discount_codes', function (Blueprint $table) {
            $table->unsignedBigInteger('price_rule_id')
            ->after('id')
            ->nullable(); // Temporary nullable for existing records

        // Add foreign key constraint
        $table->foreign('price_rule_id')
                ->references('id')
                ->on('price_rules')
                ->onDelete('cascade');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['price_rule_id']);
            
            // Then drop the column
            $table->dropColumn('price_rule_id');
        });
    }
};
