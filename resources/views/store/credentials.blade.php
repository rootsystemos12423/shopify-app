<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Integração com Shopify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #fafafa;
        }
        .shopify-gradient {
            background: linear-gradient(135deg, #f6f8fa 0%, #f0f4f9 100%);
        }
        .shopify-badge {
            background-color: #96BF48;
        }
    </style>
</head>
<body class="min-h-screen shopify-gradient">
    <div class="container mx-auto px-4 py-12">
        <!-- Header -->
        <header class="text-center mb-12">
            <div class="flex justify-center items-center mb-6">
                <img src="https://ongoingwarehouse.com/Pictures/1280px-Shopify_logo_2018.svg.webp" 
                     alt="Shopify" class="h-12 mr-3">
                <span class="text-2xl font-bold text-gray-800">+</span>
                <img src="https://ongoingwarehouse.com/Pictures/1280px-Shopify_logo_2018.svg.webp" 
                     alt="Seu Logo" class="h-12 ml-3">
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3">Conecte sua loja Shopify</h1>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">Integre seus produtos, clientes e pedidos em poucos passos</p>
        </header>

        <!-- Form Card -->
        <div class="max-w-2xl mx-auto bg-white rounded-xl shadow-md overflow-hidden p-8">
            <div class="mb-8">
                <div class="flex items-center mb-4">
                    <span class="shopify-badge text-white text-xs font-semibold px-2 py-1 rounded mr-2">SHOPIFY</span>
                    <h2 class="text-xl font-semibold text-gray-800">Credenciais de API</h2>
                </div>
                <p class="text-gray-600 text-sm">Preencha com as credenciais do seu app Shopify. Você pode encontrar essas informações na seção <strong>App development</strong> do seu admin Shopify.</p>
            </div>

            <form id="shopifyIntegrationForm">
                <!-- Loja Shopify -->
                <div class="mb-6">
                    <label for="shopifyStore" class="block text-sm font-medium text-gray-700 mb-1">Domínio da sua loja Shopify</label>
                    <div class="mt-1 flex rounded-md shadow-sm">
                        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                            https://
                        </span>
                        <input 
                            type="text" 
                            id="shopifyStore" 
                            class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border border-gray-300 focus:ring-gray-800 focus:border-gray-800 outline-none transition"
                            placeholder="nomedaloja"
                            required
                        >
                        <span class="inline-flex items-center px-3 border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm rounded-r-md">
                            .myshopify.com
                        </span>
                    </div>
                </div>

                <!-- API Key -->
                <div class="mb-6">
                    <label for="apiKey" class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                    <input 
                        type="text" 
                        id="apiKey" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-gray-800 outline-none transition"
                        placeholder="shpua_xxxxxxxxxxxxxxxx"
                        required
                    >
                </div>

                <!-- API Secret -->
                <div class="mb-6">
                    <label for="apiSecret" class="block text-sm font-medium text-gray-700 mb-1">API Secret Key</label>
                    <input 
                        type="password" 
                        id="apiSecret" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-gray-800 outline-none transition"
                        placeholder="shpss_xxxxxxxxxxxxxxxx"
                        required
                    >
                    <p class="mt-1 text-xs text-gray-500">Necessário para sincronizar temas e assets</p>
                </div>

                <!-- Admin API Access Token -->
                <div class="mb-6">
                    <label for="adminToken" class="block text-sm font-medium text-gray-700 mb-1">Admin API Access Token</label>
                    <input 
                        type="password" 
                        id="adminToken" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-gray-800 outline-none transition"
                        placeholder="shpat_xxxxxxxxxxxxxxxx"
                        required
                    >
                    <p class="mt-1 text-xs text-gray-500">Necessário para sincronizar pedidos e clientes</p>
                </div>

                <!-- Webhook Secret -->
                <div class="mb-8">
                    <label for="webhookSecret" class="block text-sm font-medium text-gray-700 mb-1">Webhook Secret (Opcional)</label>
                    <input 
                        type="password" 
                        id="webhookSecret" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-gray-800 outline-none transition"
                        placeholder="whs_xxxxxxxxxxxxxxxx"
                    >
                    <p class="mt-1 text-xs text-gray-500">Recomendado para atualizações em tempo real</p>
                </div>

                <div class="flex items-center mb-6">
                    <input 
                        id="syncProducts" 
                        type="checkbox" 
                        class="h-4 w-4 text-gray-800 focus:ring-gray-800 border-gray-300 rounded"
                        checked
                    >
                    <label for="syncProducts" class="ml-2 block text-sm text-gray-700">
                        Sincronizar produtos e coleções automaticamente
                    </label>
                </div>

                <div class="flex items-center mb-6">
                  <input 
                      id="syncAssets" 
                      type="checkbox" 
                      class="h-4 w-4 text-gray-800 focus:ring-gray-800 border-gray-300 rounded"
                      checked
                  >
                  <label for="syncAssets" class="ml-2 block text-sm text-gray-700">
                      Sincronizar assets e temas automaticamente
                  </label>
              </div>

              <div class="flex items-center mb-6">
                  <input 
                      id="syncDiscounts" 
                      type="checkbox" 
                      class="h-4 w-4 text-gray-800 focus:ring-gray-800 border-gray-300 rounded"
                      checked
                  >
                  <label for="syncDiscounts" class="ml-2 block text-sm text-gray-700">
                      Sincronizar cupons automaticamente
                  </label>
              </div>

              <div class="flex items-center mb-6">
                  <input 
                      id="syncCustomers" 
                      type="checkbox" 
                      class="h-4 w-4 text-gray-800 focus:ring-gray-800 border-gray-300 rounded"
                      checked
                  >
                  <label for="syncCustomers" class="ml-2 block text-sm text-gray-700">
                      Sincronizar clientes automaticamente
                  </label>
              </div>

              <div class="flex items-center mb-6">
                  <input 
                      id="syncUsersAndPermissions" 
                      type="checkbox" 
                      class="h-4 w-4 text-gray-800 focus:ring-gray-800 border-gray-300 rounded"
                      disabled
                  >
                  <label for="syncUsersAndPermissions" class="ml-2 block text-sm text-gray-700">
                      Sincronizar usuário e permissões automaticamente (API DEPRECATED)
                  </label>
              </div>

                <div class="flex items-center mb-6">
                    <input 
                        id="syncOrders" 
                        type="checkbox" 
                        class="h-4 w-4 text-gray-800 focus:ring-gray-800 border-gray-300 rounded"
                        checked
                    >
                    <label for="syncOrders" class="ml-2 block text-sm text-gray-700">
                        Sincronizar histórico de pedidos
                    </label>
                </div>

                <button 
                    type="submit" 
                    class="w-full bg-[#96BF48] hover:bg-[#7fa53d] text-white font-medium py-3 px-4 rounded-md transition duration-150 ease-in-out shadow-sm flex items-center justify-center"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                    Conectar Loja
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-gray-100">
                <div class="text-sm text-gray-500">
                    <p class="mb-2">Onde encontrar essas informações?</p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li>Acesse o <strong class="font-bold hover:underline">Admin Shopify</strong></li>
                        <li>Navegue até <strong>Apps > App development</strong></li>
                        <li>Selecione seu app ou crie um novo</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Security Info -->
        <div class="max-w-2xl mx-auto mt-8 bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <div class="flex items-start">
                <svg class="h-5 w-5 text-gray-700 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <div>
                    <h3 class="font-semibold text-gray-800 mb-1">Suas credenciais estão seguras</h3>
                    <p class="text-sm text-gray-600">Todas as informações são criptografadas durante a transmissão e armazenadas com segurança. Nossa aplicação tem acesso apenas aos recursos necessários para a integração.</p>
                </div>
            </div>
        </div>
    </div>

    <script>

        document.querySelectorAll('input[type="password"]').forEach(input => {
                    const toggle = document.createElement('span');
                    toggle.className = 'absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer';
                    toggle.innerHTML = `
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    `;
                    
                    input.parentNode.classList.add('relative');
                    input.parentNode.appendChild(toggle);
                    
                    toggle.addEventListener('click', () => {
                        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                        input.setAttribute('type', type);
                        
                        // Altera o ícone
                        toggle.querySelector('svg').innerHTML = type === 'password' ? `
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        ` : `
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                        `;
                    });
                });

        document.getElementById('shopifyIntegrationForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Obter elementos do DOM
            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');

            const store_token = sessionStorage.getItem('store_token');
            if (!store_token) {
                showAlert('Sessão inválida. Por favor, recarregue a página.', 'error');
                return;
            }

            const loadingSpinner = document.createElement('span');
            loadingSpinner.className = 'animate-spin ml-2 h-5 w-5';
            loadingSpinner.innerHTML = `
                <svg class="h-full w-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
            `;
            
            // Obter valores do formulário
            const storeDomain = document.getElementById('shopifyStore').value;
            const apiKey = document.getElementById('apiKey').value;
            const apiSecret = document.getElementById('apiSecret').value;
            const adminToken = document.getElementById('adminToken').value;
            const webhookSecret = document.getElementById('webhookSecret').value;
            
            // Configurações de sincronização
            const syncOptions = {
                products: document.getElementById('syncProducts').checked,
                assets: document.getElementById('syncAssets').checked,
                discounts: document.getElementById('syncDiscounts').checked,
                customers: document.getElementById('syncCustomers').checked,
                users: document.getElementById('syncUsersAndPermissions').checked,
                orders: document.getElementById('syncOrders').checked
            };
            
            // Validação
            if (!storeDomain || !apiKey || !apiSecret || !adminToken) {
                showAlert('Por favor, preencha todos os campos obrigatórios', 'error');
                return;
            }
            
            try {
                // Mostrar estado de loading
                submitBtn.disabled = true;
                submitBtn.appendChild(loadingSpinner);
                
                // Enviar dados para o backend
                const response = await fetch('/api/create/stores/integration', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        shopify_domain: storeDomain,
                        api_key: apiKey,
                        api_secret: apiSecret,
                        admin_token: adminToken,
                        webhook_secret: webhookSecret || null,
                        sync_options: syncOptions,
                        store_token: store_token
                    })
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'Erro ao conectar com Shopify');
                }
                
                // Armazenar tokens de forma segura
                if (data.data.access_token) {
                    localStorage.setItem('shopify_access_token', data.data.access_token);
                    sessionStorage.setItem('shopify_store_domain', storeDomain);
                }
                
                // Mostrar mensagem de sucesso
                showAlert(`Conexão com ${storeDomain}.myshopify.com estabelecida com sucesso!`, 'success');
               
                /*
                // Redirecionar ou atualizar a página
                setTimeout(() => {
                    window.location.href = data.data.redirect_url || '/dashboard';
                }, 2000);
                */
               
            } catch (error) {
                console.error('Erro na integração:', error);
                showAlert(error.message || 'Erro ao conectar com Shopify', 'error');
            } finally {
                // Restaurar botão
                submitBtn.disabled = false;
                if (submitBtn.contains(loadingSpinner)) {
                    submitBtn.removeChild(loadingSpinner);
                }
            }
        });

        // Função para mostrar alertas (substitua pela sua implementação)
        function showAlert(message, type = 'success') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `fixed top-4 right-4 p-4 rounded-md shadow-lg ${
                type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
            }`;
            alertDiv.innerHTML = `
                <div class="flex items-center">
                    <svg class="h-6 w-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${
                            type === 'success' ? 
                            'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"' : 
                            'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"'
                        }></path>
                    </svg>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Remover após 5 segundos
            setTimeout(() => {
                alertDiv.classList.add('opacity-0', 'transition-opacity', 'duration-300');
                setTimeout(() => alertDiv.remove(), 300);
            }, 5000);
        }
    </script>
</body>
</html>