<?php

namespace App\Services;

use Liquid\Context;

class AddressProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        // Adicionar objeto de endereço atual se estamos em uma página de conta
        if (in_array($params['template'], ['account', 'customers/account', 'customers/addresses'])) {
            $context->set('customer_address', $this->getAddressData());
        }
        
        // Adicionar todos os países disponíveis
        $context->set('all_country_option_tags', $this->getAllCountryOptionTags());
        $context->set('country_option_tags', $this->getCountryOptionTags());
        $context->set('all_countries', $this->getAllCountries());
    }
    
    private function getAddressData(): array
    {
        return [
            'id' => 101,
            'first_name' => 'João',
            'last_name' => 'Silva',
            'name' => 'João Silva',
            'company' => 'Empresa Exemplo',
            'address1' => 'Rua Exemplo, 123',
            'address2' => 'Apto 45',
            'city' => 'São Paulo',
            'province' => 'São Paulo',
            'province_code' => 'SP',
            'country' => 'Brasil',
            'country_code' => 'BR',
            'zip' => '01234-567',
            'phone' => '(11) 99999-9999',
            'street' => 'Rua Exemplo, 123, Apto 45',
            'default' => true
        ];
    }
    
    private function getAllCountryOptionTags(): string
    {
        return '<option value="BR" selected="selected">Brasil</option>' .
               '<option value="US">Estados Unidos</option>' .
               '<option value="AR">Argentina</option>' .
               '<option value="PT">Portugal</option>' .
               '<option value="DE">Alemanha</option>' .
               '<option value="FR">França</option>' .
               '<option value="ES">Espanha</option>' .
               '<option value="IT">Itália</option>' .
               '<option value="JP">Japão</option>' .
               '<option value="CA">Canadá</option>';
    }
    
    private function getCountryOptionTags(): string
    {
        return $this->getAllCountryOptionTags();
    }
    
    private function getAllCountries(): array
    {
        return [
            [
                'name' => 'Brasil',
                'code' => 'BR',
                'provinces' => [
                    [
                        'name' => 'Acre',
                        'code' => 'AC'
                    ],
                    [
                        'name' => 'Alagoas',
                        'code' => 'AL'
                    ],
                    [
                        'name' => 'Amapá',
                        'code' => 'AP'
                    ],
                    [
                        'name' => 'Amazonas',
                        'code' => 'AM'
                    ],
                    [
                        'name' => 'Bahia',
                        'code' => 'BA'
                    ],
                    [
                        'name' => 'Ceará',
                        'code' => 'CE'
                    ],
                    [
                        'name' => 'Distrito Federal',
                        'code' => 'DF'
                    ],
                    [
                        'name' => 'Espírito Santo',
                        'code' => 'ES'
                    ],
                    [
                        'name' => 'Goiás',
                        'code' => 'GO'
                    ],
                    [
                        'name' => 'Maranhão',
                        'code' => 'MA'
                    ],
                    [
                        'name' => 'Mato Grosso',
                        'code' => 'MT'
                    ],
                    [
                        'name' => 'Mato Grosso do Sul',
                        'code' => 'MS'
                    ],
                    [
                        'name' => 'Minas Gerais',
                        'code' => 'MG'
                    ],
                    [
                        'name' => 'Pará',
                        'code' => 'PA'
                    ],
                    [
                        'name' => 'Paraíba',
                        'code' => 'PB'
                    ],
                    [
                        'name' => 'Paraná',
                        'code' => 'PR'
                    ],
                    [
                        'name' => 'Pernambuco',
                        'code' => 'PE'
                    ],
                    [
                        'name' => 'Piauí',
                        'code' => 'PI'
                    ],
                    [
                        'name' => 'Rio de Janeiro',
                        'code' => 'RJ'
                    ],
                    [
                        'name' => 'Rio Grande do Norte',
                        'code' => 'RN'
                    ],
                    [
                        'name' => 'Rio Grande do Sul',
                        'code' => 'RS'
                    ],
                    [
                        'name' => 'Rondônia',
                        'code' => 'RO'
                    ],
                    [
                        'name' => 'Roraima',
                        'code' => 'RR'
                    ],
                    [
                        'name' => 'Santa Catarina',
                        'code' => 'SC'
                    ],
                    [
                        'name' => 'São Paulo',
                        'code' => 'SP'
                    ],
                    [
                        'name' => 'Sergipe',
                        'code' => 'SE'
                    ],
                    [
                        'name' => 'Tocantins',
                        'code' => 'TO'
                    ]
                ]
            ],
            [
                'name' => 'Estados Unidos',
                'code' => 'US',
                'provinces' => [
                    [
                        'name' => 'Alabama',
                        'code' => 'AL'
                    ],
                    [
                        'name' => 'Alaska',
                        'code' => 'AK'
                    ],
                    [
                        'name' => 'Arizona',
                        'code' => 'AZ'
                    ],
                    // ... outros estados dos EUA
                ]
            ],
            // ... outros países
        ];
    }
}