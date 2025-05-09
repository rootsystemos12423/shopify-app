<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceRule extends Model
{

    protected $casts = [
        'value' => 'decimal:2',
        'once_per_customer' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'entitled_product_ids' => 'array',
        'entitled_variant_ids' => 'array',
        'entitled_collection_ids' => 'array',
        'entitled_country_ids' => 'array',
        'prerequisite_product_ids' => 'array',
        'prerequisite_variant_ids' => 'array',
        'prerequisite_collection_ids' => 'array',
        'prerequisite_customer_ids' => 'array',
        'customer_segment_prerequisite_ids' => 'array',
        'prerequisite_subtotal_range' => 'array',
        'prerequisite_quantity_range' => 'array',
        'prerequisite_shipping_price_range' => 'array',
        'prerequisite_to_entitlement_quantity_ratio' => 'array',
        'prerequisite_to_entitlement_purchase' => 'array',
    ];

    protected $fillable = [
        'id',
        'admin_graphql_api_id',
        'title',
        'value_type',
        'value',
        'customer_selection',
        'target_type',
        'target_selection',
        'allocation_method',
        'allocation_limit',
        'once_per_customer',
        'usage_limit',
        'starts_at',
        'ends_at',
        'entitled_product_ids',
        'entitled_variant_ids',
        'entitled_collection_ids',
        'entitled_country_ids',
        'prerequisite_product_ids',
        'prerequisite_variant_ids',
        'prerequisite_collection_ids',
        'prerequisite_customer_ids',
        'customer_segment_prerequisite_ids',
        'prerequisite_subtotal_range',
        'prerequisite_quantity_range',
        'prerequisite_shipping_price_range',
        'prerequisite_to_entitlement_quantity_ratio',
        'prerequisite_to_entitlement_purchase',
        'created_at',
        'updated_at',
    ];

    public function discountCodes()
    {
        return $this->hasMany(DiscountCode::class);
    }

    // Verifica se a regra está ativa atualmente
    public function getIsActiveAttribute()
    {
        $now = now();
        return $this->starts_at <= $now && 
               ($this->ends_at === null || $this->ends_at >= $now);
    }

    // Verifica se um produto específico está qualificado
    public function isProductEligible($productId, $variantId = null)
    {
        // Todos os produtos estão qualificados
        if (empty($this->entitled_product_ids) && 
            empty($this->entitled_variant_ids) && 
            empty($this->entitled_collection_ids)) {
            return true;
        }

        // Verifica por product_id
        if (!empty($this->entitled_product_ids)) {
            return in_array($productId, $this->entitled_product_ids);
        }

        // Verifica por variant_id
        if (!empty($this->entitled_variant_ids) && $variantId) {
            return in_array($variantId, $this->entitled_variant_ids);
        }

        return false;
    }
}
