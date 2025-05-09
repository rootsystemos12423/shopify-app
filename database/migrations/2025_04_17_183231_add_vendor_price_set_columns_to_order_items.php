<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up()
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Primeiro adicione a coluna total_discount se não existir
            if (!Schema::hasColumn('order_items', 'total_discount')) {
                $table->decimal('total_discount', 10, 2)
                      ->default(0)
                      ->after('price'); // Ou após outra coluna relevante
            }

            // Agora adicione as novas colunas
            $table->string('vendor')
                  ->after('name')
                  ->nullable();

            $table->json('price_set')
                  ->after('price')
                  ->nullable();

            $table->json('total_discount_set')
                  ->after('total_discount') // Agora esta coluna existe
                  ->nullable();
        });
    }

    public function down()
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'vendor',
                'price_set',
                'total_discount_set',
                'total_discount' // Opcional: remova se quiser desfazer completamente
            ]);
        });
    }
};
