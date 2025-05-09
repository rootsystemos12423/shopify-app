<?php

namespace App\Services;

use Liquid\Context;

class SellingPlanProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        // Adicionar objeto de plano de vendas para páginas de produto
        if ($params['template'] === 'product') {
            $context->set('selling_plan_allocation', $this->getSellingPlanAllocationData());
            $context->set('selling_plan', $this->getSellingPlanData());
            $context->set('selling_plan_group', $this->getSellingPlanGroupData());
            
            // Adicionar planos de venda ao produto atual
            $context->mergeArray(['product' => ['selling_plan_groups' => [$this->getSellingPlanGroupData()]]]);
            $context->mergeArray(['product' => ['requires_selling_plan' => false]]);
        }
    }
    
    private function getSellingPlanAllocationData(): array
    {
        return [
            'price_adjustments' => [
                [
                    'position' => 1,
                    'price' => 4491,
                    'compare_at_price' => 4990,
                    'per_delivery_price' => 4491,
                    'unit_price' => 4491,
                    'value' => 10,
                    'value_type' => 'percentage'
                ]
            ],
            'remaining_balance_charge_policy' => 'full_price',
            'checkout_charge_policy' => 'recurring_price',
            'selling_plan' => $this->getSellingPlanData(),
            'selling_plan_group_id' => 'spg_123456',
            'selling_plan_id' => 'sp_123456',
            'variant_id' => 12345
        ];
    }
    
    private function getSellingPlanData(): array
    {
        return [
            'id' => 'sp_123456',
            'name' => 'Assinatura Mensal',
            'description' => 'Receba este produto todo mês e economize 10%',
            'options' => [
                [
                    'name' => 'Intervalo de Entrega',
                    'position' => 1,
                    'value' => 'Mensal'
                ]
            ],
            'recurring_deliveries' => true,
            'price_adjustments' => [
                [
                    'position' => 1,
                    'order_count' => null,
                    'value' => 10,
                    'value_type' => 'percentage'
                ]
            ],
            'checkout_charge' => [
                'value' => 'recurring_price',
                'value_type' => 'price'
            ],
            'delivery_policy' => [
                'anchors' => [
                    [
                        'day_of_month' => 1
                    ]
                ],
                'interval' => 'month',
                'interval_count' => 1,
                'cutoff_day_of_month' => 25,
                'cutoff_day_of_week' => 5,
                'pre_anchor_behavior' => 'next'
            ],
            'group' => $this->getSellingPlanGroupData()
        ];
    }
    
    private function getSellingPlanGroupData(): array
    {
        return [
            'id' => 'spg_123456',
            'name' => 'Assinatura',
            'options' => [
                [
                    'name' => 'Intervalo de Entrega',
                    'position' => 1,
                    'values' => ['Mensal', 'Trimestral', 'Semestral']
                ]
            ],
            'selling_plans' => [
                $this->getSellingPlanData(),
                [
                    'id' => 'sp_123457',
                    'name' => 'Assinatura Trimestral',
                    'description' => 'Receba este produto a cada 3 meses e economize 15%',
                    'options' => [
                        [
                            'name' => 'Intervalo de Entrega',
                            'position' => 1,
                            'value' => 'Trimestral'
                        ]
                    ],
                    'recurring_deliveries' => true,
                    'price_adjustments' => [
                        [
                            'position' => 1,
                            'order_count' => null,
                            'value' => 15,
                            'value_type' => 'percentage'
                        ]
                    ]
                ],
                [
                    'id' => 'sp_123458',
                    'name' => 'Assinatura Semestral',
                    'description' => 'Receba este produto a cada 6 meses e economize 20%',
                    'options' => [
                        [
                            'name' => 'Intervalo de Entrega',
                            'position' => 1,
                            'value' => 'Semestral'
                        ]
                    ],
                    'recurring_deliveries' => true,
                    'price_adjustments' => [
                        [
                            'position' => 1,
                            'order_count' => null,
                            'value' => 20,
                            'value_type' => 'percentage'
                        ]
                    ]
                ]
            ],
            'app_id' => 'subscription_app',
            'merchant_code' => 'subscription_app_merchant',
            'summary' => 'Assine e economize até 20%',
            'products' => []
        ];
    }
}