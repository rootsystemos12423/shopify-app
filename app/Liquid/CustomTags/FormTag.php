<?php

namespace App\Liquid\CustomTags;

use Liquid\AbstractBlock;
use Liquid\Context;
use Liquid\Liquid;
use Liquid\LiquidException;
use Liquid\FileSystem;
use Liquid\Regexp;

class FormTag extends AbstractBlock
{
    /**
     * The tag's name
     *
     * @return string
     */
    public static function tagName(): string
    {
        return 'form';
    }

    /**
     * Regular expressions for parsing tag markup
     */
    private static $FormTagSyntax = '/(["\'])(.*?)\\1(?:\\s*,\\s*(.+?))?(?:\\s+(.+))?$/';
    private static $AttributeSyntax = '/([a-zA-Z0-9_-]+)\\s*:\\s*(["\'])(.*?)\\2/';

    /**
     * Form parameters
     */
    private $formType;
    private $formObject;
    protected $attributes = [];

    /**
     * Constructor
     *
     * @param string $markup
     * @param array $tokens
     * @param FileSystem $fileSystem
     * @throws LiquidException
     */
    public function __construct($markup, array &$tokens, FileSystem $fileSystem = null)
    {
        parent::__construct($markup, $tokens, $fileSystem);

        if (!preg_match(self::$FormTagSyntax, $markup, $matches)) {
            throw new LiquidException("Syntax error in 'form' tag - Valid syntax: form 'type'[, object][, param:value, ...]");
        }

        $this->formType = $matches[2];
        
        // Parse form object (optional)
        if (!empty($matches[3])) {
            $this->formObject = trim($matches[3]);
        }
        
        // Parse attributes (optional)
        if (!empty($matches[4])) {
            preg_match_all(self::$AttributeSyntax, $matches[4], $attributeMatches, PREG_SET_ORDER);
            foreach ($attributeMatches as $match) {
                $this->attributes[$match[1]] = $match[3];
            }
        }
    }

    /**
     * Renders the form tag
     *
     * @param Context $context
     * @return string
     */
    public function render($context)
    {
        // Resolve form object if provided
        $resolvedObject = null;
        if (!empty($this->formObject)) {
            $resolvedObject = $context->get($this->formObject);
        }

        // Add form object to context for filters like payment_terms
        $context->push([
            'form' => [
                'type' => $this->formType,
                'object' => $resolvedObject,
                'errors' => $this->getFormErrors($context),
                'posted' => $this->wasFormPosted($context),
            ]
        ]);

        // Generate the opening form tag with attributes
        $output = $this->renderFormOpen($context, $resolvedObject);
        
        // Render the inner content of the form
        $output .= parent::render($context);
        
        // Close the form
        $output .= '</form>';
        
        // Remove form object from context
        $context->pop();
        
        return $output;
    }

    /**
     * Renders the opening form tag with all attributes and hidden fields
     *
     * @param Context $context
     * @param mixed $formObject
     * @return string
     */
    private function renderFormOpen($context, $formObject)
    {
        $formAttributes = $this->getFormAttributes($formObject);
        $hiddenFields = $this->getHiddenFields($context, $formObject);
        
        // Build opening tag with attributes
        $output = '<form';
        foreach ($formAttributes as $attr => $value) {
            if ($value !== null) {
                $output .= ' ' . $attr . '="' . htmlspecialchars($value) . '"';
            }
        }
        $output .= '>';
        
        // Add hidden fields
        foreach ($hiddenFields as $name => $value) {
            $output .= '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" />';
        }
        
        // Add CSRF token if applicable
        $output .= $this->getCsrfField();
        
        return $output;
    }

    /**
     * Determine form attributes based on form type and resolved object
     *
     * @param mixed $formObject
     * @return array
     */
    private function getFormAttributes($formObject)
    {
        // Default attributes applied to all forms
        $attributes = [
            'method' => 'post',
            'accept-charset' => 'UTF-8',
        ];
        
        // Apply action URL based on form type
        $attributes['action'] = $this->getFormAction($formObject);
        
        // Apply any user-specified attributes
        foreach ($this->attributes as $key => $value) {
            $attributes[$key] = $value;
        }
        
        // Apply form type specific attributes
        switch ($this->formType) {
            case 'product':
                if (!isset($attributes['id'])) {
                    $productId = is_array($formObject) && isset($formObject['id']) ? $formObject['id'] : '0';
                    $attributes['id'] = 'product_form_' . $productId;
                }
                $attributes['enctype'] = 'multipart/form-data';
                $attributes['data-product-form'] = '';
                break;
                
            case 'cart':
                if (!isset($attributes['id'])) {
                    $attributes['id'] = 'cart_form';
                }
                if (!isset($attributes['class'])) {
                    $attributes['class'] = 'shopify-cart-form';
                }
                $attributes['enctype'] = 'multipart/form-data';
                break;
                
            case 'customer_login':
                if (!isset($attributes['id'])) {
                    $attributes['id'] = 'customer_login';
                }
                break;
                
            case 'create_customer':
                if (!isset($attributes['id'])) {
                    $attributes['id'] = 'create_customer';
                }
                break;
                
            case 'contact':
                if (!isset($attributes['class'])) {
                    $attributes['class'] = 'contact-form';
                }
                break;
                
            case 'localization':
                if (!isset($attributes['id'])) {
                    $attributes['id'] = 'localization_form';
                }
                if (!isset($attributes['class'])) {
                    $attributes['class'] = 'shopify-localization-form';
                }
                $attributes['enctype'] = 'multipart/form-data';
                break;
                
            case 'currency':
                if (!isset($attributes['id'])) {
                    $attributes['id'] = 'currency_form';
                }
                if (!isset($attributes['class'])) {
                    $attributes['class'] = 'shopify-currency-form';
                }
                $attributes['enctype'] = 'multipart/form-data';
                break;
                
            case 'search':
                if (!isset($attributes['role'])) {
                    $attributes['role'] = 'search';
                }
                // Search forms typically use GET instead of POST
                $attributes['method'] = 'get';
                break;
                
            case 'storefront_password':
                if (!isset($attributes['id'])) {
                    $attributes['id'] = 'login_form';
                }
                if (!isset($attributes['class'])) {
                    $attributes['class'] = 'storefront-password-form';
                }
                break;
                
            case 'recover_customer_password':
                if (!isset($attributes['id'])) {
                    $attributes['id'] = 'recover_customer_password';
                }
                break;
        }
        
        return $attributes;
    }

    /**
     * Get the form action URL based on form type
     *
     * @param mixed $formObject
     * @return string
     */
    private function getFormAction($formObject)
    {
        switch ($this->formType) {
            case 'product':
                return '/cart/add';
                
            case 'cart':
                return '/cart';
                
            case 'customer_login':
                return '/account/login';
                
            case 'create_customer':
                return '/account';
                
            case 'customer_address':
                return '/account/addresses';
                
            case 'recover_customer_password':
                return '/account/recover';
                
            case 'reset_customer_password':
                return '/account/reset';
                
            case 'activate_customer_password':
                return '/account/activate';
                
            case 'contact':
                return '/contact';
                
            case 'localization':
                return '/localization';
                
            case 'currency':
                return '/cart/update';
                
            case 'search':
                return '/search';
                
            case 'new_comment':
                if (is_array($formObject) && isset($formObject['id']) && isset($formObject['blog']['handle'])) {
                    return '/blogs/' . $formObject['blog']['handle'] . '/' . $formObject['id'] . '/comments';
                }
                return '/blogs/news/comments';
                
            case 'storefront_password':
                return '/password';
                
            default:
                return '/';
        }
    }

    /**
     * Generate hidden input fields based on form type
     *
     * @param Context $context
     * @param mixed $formObject
     * @return array
     */
    private function getHiddenFields($context, $formObject)
    {
        $fields = [
            'form_type' => $this->formType,
            'utf8' => '✓',
        ];
        
        switch ($this->formType) {
            case 'product':
                // Handle direct ID input from form
                if (isset($this->attributes['id']) && strpos($this->attributes['id'], 'id:') === 0) {
                    $fields['id'] = substr($this->attributes['id'], 3);
                } 
                // Handle product variant
                elseif (is_array($formObject)) {
                    // Check if variant ID is explicitly set in context
                    $variantId = null;
                    
                    // Get selected_or_first_available_variant.id if available
                    if (isset($formObject['selected_or_first_available_variant']['id'])) {
                        $variantId = $formObject['selected_or_first_available_variant']['id'];
                    } 
                    // Or use first available variant
                    elseif (isset($formObject['variants']) && is_array($formObject['variants']) && !empty($formObject['variants'])) {
                        foreach ($formObject['variants'] as $variant) {
                            if (isset($variant['available']) && $variant['available'] === true) {
                                $variantId = $variant['id'];
                                break;
                            }
                        }
                        
                        // If no available variant, use the first one
                        if ($variantId === null && isset($formObject['variants'][0]['id'])) {
                            $variantId = $formObject['variants'][0]['id'];
                        }
                    }
                    
                    // Set the variant ID in the form
                    if ($variantId !== null) {
                        $fields['id'] = $variantId;
                    } 
                    // Fall back to product ID if needed
                    elseif (isset($formObject['id'])) {
                        $fields['product-id'] = $formObject['id'];
                    }
                }
                break;
                
            case 'customer_address':
                if (is_array($formObject) && isset($formObject['id'])) {
                    $fields['address_id'] = $formObject['id'];
                }
                break;
                
            case 'localization':
                $fields['_method'] = 'put';
                // Current page URL should be used in a real implementation
                $fields['return_to'] = $this->getCurrentUrl($context);
                break;
                
            case 'currency':
                $fields['form_type'] = 'currency';
                break;
                
            case 'guest_login':
                $fields['guest'] = '1';
                break;
                
            case 'customer_login':
                // Handle redirect after login
                $fields['return_to'] = $this->getCurrentUrl($context);
                break;
                
                case 'reset_customer_password':
                    // Include reset token if available
                    $resetToken = $context->get('resetToken');
                    if ($resetToken !== null) {
                        $fields['token'] = $resetToken;
                    }
                    break;
        }
        
        return $fields;
    }
    
    /**
     * Get CSRF protection field if applicable
     *
     * @return string
     */
    private function getCsrfField()
    {
        // In real implementation, generate and validate CSRF tokens
        // For the purpose of this example, we'll return a simple placeholder
        return '<input type="hidden" name="authenticity_token" value="csrf_token_would_go_here" />';
    }
    
    /**
     * Get the current page URL for redirects
     *
     * @param Context $context
     * @return string
     */
    private function getCurrentUrl($context)
    {
        // In a real implementation, this would use the actual current URL
        // For the purpose of this example, we'll check if there's a 'request' object
        // in context that contains the current URL, otherwise return root
        if ($context->hasKey('request') && isset($context->get('request')['path'])) {
            return $context->get('request')['path'];
        }
        
        return '/';
    }
    
    /**
     * Get any form errors that might exist
     *
     * @param Context $context
     * @return array
     */
    private function getFormErrors($context)
    {
        // In a real implementation, this would retrieve errors from session/cookies
        // For the purpose of this example, we'll check if there's a 'form_errors' object
        // in context
        if ($context->hasKey('form_errors')) {
            return $context->get('form_errors');
        }
        
        return [];
    }
    
    /**
     * Check if the form was posted
     *
     * @param Context $context
     * @return bool
     */
    private function wasFormPosted($context)
    {
        // In a real implementation, this would check if the current request is
        // a POST and if the form was submitted
        return false;
    }
    
    /**
     * Register filters for form functionality
     *
     * @param \Liquid\Template $template
     */
    public static function registerFilters($template)
    {
        // Register the payment_terms filter
        $template->registerFilter('payment_terms', function ($form) {
            if (!is_array($form) || !isset($form['object']) || $form['type'] !== 'product') {
                return '';
            }
            
            // In a real implementation, this would generate the payment terms HTML
            // based on the product and store settings
            $product = $form['object'];
            $html = '<div class="shopify-payment-terms">';
            
            if (isset($product['requires_selling_plan']) && $product['requires_selling_plan']) {
                $html .= '<div class="shopify-payment-terms__subscription">This is a subscription product</div>';
            } else {
                $html .= '<div class="shopify-payment-terms__installments">Pay in 4 interest-free installments</div>';
            }
            
            $html .= '</div>';
            
            return $html;
        });
    }
}