<?php

namespace App\Services;

use Liquid\Context;

class CartProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        $context->set('cart', $this->getCartData());
    }
    
    private function getCartData(): array
    {
        return [
            'attributes' => [],
            'cart_level_discount_applications' => [],
            'currency' => 'BRL',
            'discount_applications' => [],
            'discounts' => [],
            'empty' => true,
            'item_count' => 0,
            'items' => [],
            'items_subtotal_price' => 0,
            'note' => null,
            'original_total_price' => 0,
            'taxes_included' => true,
            'total_discount' => 0,
            'total_price' => 0,
            'total_weight' => 0,
            'terms_and_conditions' => [
                'disclosure_title' => 'Termos e Condições',
                'disclosure_text' => 'Ao finalizar este pedido, você concorda com nossos Termos de Serviço e Política de Privacidade.'
            ],
            'gift_cards' => [],
            'shipping_price' => 0,
            'tax_price' => 0,
            'tax_lines' => []
        ];
    }
}