<?php

namespace App\Services;

use Liquid\Context;

class FormProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        // Adicionar objetos de formulário necessários
        $context->set('form', $this->getDefaultForm());
        $context->set('comment_form', $this->getCommentForm());
        $context->set('contact_form', $this->getContactForm());
        $context->set('customer_login_form', $this->getLoginForm());
        $context->set('customer_register_form', $this->getRegisterForm());
        $context->set('create_customer_form', $this->getRegisterForm());
        $context->set('recover_customer_form', $this->getRecoverPasswordForm());
        $context->set('reset_customer_form', $this->getResetPasswordForm());
        $context->set('activate_customer_form', $this->getActivateCustomerForm());
        $context->set('address_form', $this->getAddressForm());
        $context->set('cart_form', $this->getCartForm());
        $context->set('search_form', $this->getSearchForm());
    }
    
    private function getDefaultForm(): array
    {
        return [
            'errors' => false,
            'posted_successfully' => false,
            'id' => 'contact',
            'action' => '/contact',
            'method' => 'post',
            'enctype' => 'multipart/form-data',
            'params' => [
                'form_type' => 'contact'
            ]
        ];
    }
    
    private function getCommentForm(): array
    {
        return [
            'errors' => false,
            'posted_successfully' => false,
            'id' => 'comment',
            'action' => '/blogs/noticias/tendencias-de-moda-para-o-verao-2025/comments',
            'method' => 'post',
            'enctype' => 'multipart/form-data',
            'params' => [
                'form_type' => 'comment',
                'blog_id' => 11111,
                'article_id' => 12345
            ]
        ];
    }
    
    private function getContactForm(): array
    {
        return [
            'errors' => false,
            'posted_successfully' => false,
            'id' => 'contact',
            'action' => '/contact',
            'method' => 'post',
            'enctype' => 'multipart/form-data',
            'params' => [
                'form_type' => 'contact'
            ]
        ];
    }
    
    private function getLoginForm(): array
    {
        return [
            'errors' => false,
            'posted_successfully' => false,
            'id' => 'customer_login',
            'action' => '/account/login',
            'method' => 'post',
            'enctype' => 'multipart/form-data',
            'params' => [
                'form_type' => 'customer_login'
            ]
        ];
    }
    
    private function getRegisterForm(): array
    {
        return [
            'errors' => false,
            'posted_successfully' => false,
            'id' => 'create_customer',
            'action' => '/account/register',
            'method' => 'post',
            'enctype' => 'multipart/form-data',
            'params' => [
                'form_type' => 'create_customer'
            ]
        ];
    }
    
    private function getRecoverPasswordForm(): array
    {
        return [
            'errors' => false,
            'posted_successfully' => false,
            'id' => 'recover_customer_password',
            'action' => '/account/recover',
            'method' => 'post',
            'enctype' => 'multipart/form-data',
            'params' => [
                'form_type' => 'recover_customer_password'
            ]
        ];
    }
    
    private function getResetPasswordForm(): array
    {
        return [
            'errors' => false,
            'posted_successfully' => false,
            'id' => 'reset_customer_password',
            'action' => '/account/reset',
            'method' => 'post',
            'enctype' => 'multipart/form-data',
            'params' => [
                'form_type' => 'reset_customer_password'
            ]
        ];
    }
    
    private function getActivateCustomerForm(): array
    {
        return [
            'errors' => false,
            'posted_successfully' => false,
            'id' => 'activate_customer_password',
            'action' => '/account/activate',
            'method' => 'post',
            'enctype' => 'multipart/form-data',
            'params' => [
                'form_type' => 'activate_customer_password'
            ]
        ];
    }
    
    private function getAddressForm(): array
    {
        return [
            'errors' => false,
            'posted_successfully' => false,
            'id' => 'customer_address',
            'action' => '/account/addresses',
            'method' => 'post',
            'enctype' => 'multipart/form-data',
            'params' => [
                'form_type' => 'customer_address'
            ]
        ];
    }
    
    private function getCartForm(): array
    {
        return [
            'errors' => false,
            'posted_successfully' => false,
            'id' => 'cart',
            'action' => '/cart',
            'method' => 'post',
            'enctype' => 'multipart/form-data',
            'params' => [
                'form_type' => 'cart'
            ]
        ];
    }
    
    private function getSearchForm(): array
    {
        return [
            'errors' => false,
            'posted_successfully' => false,
            'id' => 'search',
            'action' => '/search',
            'method' => 'get',
            'enctype' => 'multipart/form-data',
            'params' => [
                'form_type' => 'search'
            ]
        ];
    }
}