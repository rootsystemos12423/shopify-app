<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crie sua Loja Online</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
        .domain-option {
            transition: all 0.2s ease;
        }
        .domain-option:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="min-h-screen shopify-gradient">
    <div class="container mx-auto px-4 py-12">
        <!-- Header -->
        <header class="text-center mb-12">
            <div class="flex justify-center mb-6">
                <img src="https://ongoingwarehouse.com/Pictures/1280px-Shopify_logo_2018.svg.webp" 
                  alt="Shopify" class="h-12 mr-2">
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3">Crie sua própria loja online</h1>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">Comece a vender na internet em minutos. Personalize sua loja com seu nome e domínio.</p>
        </header>

        <!-- Form Card -->
        <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden p-8">
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-2">Configure sua loja</h2>
                <p class="text-gray-600 text-sm">Escolha um nome e domínio que represente sua marca.</p>
            </div>

            <form id="storeCreationForm">
                <!-- Nome da Loja -->
                <div class="mb-6">
                    <label for="storeName" class="block text-sm font-medium text-gray-700 mb-1">Nome da Loja</label>
                    <input 
                        type="text" 
                        id="storeName" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-gray-800 outline-none transition"
                        placeholder="ex: Minha Loja Incrível"
                        required
                    >
                </div>

                <!-- Opções de Domínio -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Domínio da Loja</label>
                    
                    <div class="space-y-3">
                        <!-- Opção 1: Domínio gratuito -->
                        <div class="domain-option">
                            <input type="radio" id="freeDomain" name="domainType" value="free" class="hidden peer" checked>
                            <label for="freeDomain" class="flex items-center p-3 border border-gray-300 rounded-md cursor-pointer peer-checked:border-gray-800 peer-checked:bg-gray-50">
                                <span class="flex items-center justify-center w-5 h-5 border border-gray-300 rounded-full mr-3 peer-checked:border-gray-800 peer-checked:bg-gray-800">
                                    <svg class="w-3 h-3 text-white hidden peer-checked:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                                <div>
                                    <div class="font-medium">Domínio Gratuito</div>
                                    <div class="text-sm text-gray-600">
                                        <span id="freeDomainPreview">seusite</span>
                                        <span class="text-gray-800 font-medium">.blackshops.com</span>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Opção 2: Domínio personalizado -->
                        <div class="domain-option">
                            <input type="radio" id="customDomain" name="domainType" value="custom" class="hidden peer">
                            <label for="customDomain" class="flex items-center p-3 border border-gray-300 rounded-md cursor-pointer peer-checked:border-gray-800 peer-checked:bg-gray-50">
                                <span class="flex items-center justify-center w-5 h-5 border border-gray-300 rounded-full mr-3 peer-checked:border-gray-800 peer-checked:bg-gray-800">
                                    <svg class="w-3 h-3 text-white hidden peer-checked:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                                <div class="flex-1">
                                    <div class="font-medium">Domínio Personalizado</div>
                                    <div class="mt-1 relative" id="customDomainContainer" style="display: none;">
                                        <div class="flex rounded-md shadow-sm">
                                            <input 
                                                type="text" 
                                                id="customDomainInput" 
                                                class="flex-1 min-w-0 block w-full px-3 py-2 rounded-l-md border border-gray-300 focus:ring-gray-800 focus:border-gray-800 outline-none text-sm"
                                                placeholder="seusite"
                                                disabled
                                            >
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">Você precisará configurar o DNS deste domínio depois</p>
                                    </div>
                                    <div id="customDomainPlaceholder" class="text-sm text-gray-600">
                                        Use seu próprio domínio (ex: seuloja.com)
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <button 
                    type="submit" 
                    class="w-full bg-[#1a1a1a] hover:bg-gray-800 text-white font-medium py-3 px-4 rounded-md transition duration-150 ease-in-out shadow-sm mt-4"
                >
                    Criar minha loja
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-gray-100">
                <p class="text-sm text-gray-500 text-center">
                    Já tem uma loja? <a href="#" class="text-[#1a1a1a] font-medium hover:underline">Acesse aqui</a>
                </p>
            </div>
        </div>
    </div>

    <script>
      // Atualiza o preview do domínio gratuito quando o nome da loja muda
      document.getElementById('storeName').addEventListener('input', function(e) {
          const storeName = e.target.value.trim().toLowerCase().replace(/\s+/g, '-');
          const freeDomainPreview = document.getElementById('freeDomainPreview');
          
          if(storeName.length > 0) {
              freeDomainPreview.textContent = storeName;
          } else {
              freeDomainPreview.textContent = 'seusite';
          }
  
          // Atualiza também o input do domínio personalizado se estiver ativo
          if(document.getElementById('customDomain').checked) {
              document.getElementById('customDomainInput').value = storeName;
          }
      });
  
      // Mostra/oculta o campo de domínio personalizado
      document.getElementById('customDomain').addEventListener('change', function(e) {
          if(e.target.checked) {
              document.getElementById('customDomainContainer').style.display = 'block';
              document.getElementById('customDomainPlaceholder').style.display = 'none';
              document.getElementById('customDomainInput').disabled = false;
              document.getElementById('customDomainInput').focus();
              
              // Preenche com o nome da loja se já existir
              const storeName = document.getElementById('storeName').value.trim().toLowerCase().replace(/\s+/g, '-');
              if(storeName.length > 0) {
                  document.getElementById('customDomainInput').value = storeName;
              }
          }
      });
  
      document.getElementById('freeDomain').addEventListener('change', function(e) {
          if(e.target.checked) {
              document.getElementById('customDomainContainer').style.display = 'none';
              document.getElementById('customDomainPlaceholder').style.display = 'block';
              document.getElementById('customDomainInput').disabled = true;
          }
      });
  
      // Validação e envio do formulário
      document.getElementById('storeCreationForm').addEventListener('submit', async function(e) {
          e.preventDefault();
          
          const storeName = document.getElementById('storeName').value.trim();
          const domainType = document.querySelector('input[name="domainType"]:checked').value;
          const submitBtn = document.querySelector('#storeCreationForm button[type="submit"]');
          const originalBtnText = submitBtn.textContent;
          
          let domain;
          
          if(domainType === 'free') {
              domain = document.getElementById('freeDomainPreview').textContent;
          } else {
              domain = document.getElementById('customDomainInput').value.trim();
              if(!domain) {
                  showAlert('Por favor, insira um domínio personalizado', 'error');
                  return;
              }
          }
          
          // Desabilita o botão durante o processamento
          submitBtn.disabled = true;
          submitBtn.innerHTML = `
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Processando...
          `;
          
          try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            const response = await fetch('/api/create/stores', {
                  method: 'POST',
                  credentials: 'include',
                  headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token
                  },
                  body: JSON.stringify({
                        name: storeName,
                        domain: domain,
                        personal_token: '{{ auth()->user()->personal_token }}',
                  })
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                  throw new Error(data.message || 'Erro ao criar loja');
            }
            
            // Alternativa mais segura (sessionStorage)
            sessionStorage.setItem('store_token', data.data.access_token);
            
            showAlert(`Loja "${storeName}" criada com sucesso! Redirecionando...`, 'success');
            
            // Redireciona após 2 segundos
            setTimeout(() => {
                  window.location.href = data.data.admin_url;
            }, 2000);
            

            } catch (error) {
              console.error('Erro:', error);
              showAlert(error.message || 'Ocorreu um erro ao criar sua loja. Por favor, tente novamente.', 'error');
          } finally {
              // Restaura o botão
              submitBtn.disabled = false;
              submitBtn.textContent = originalBtnText;
          }
      });
      
      // Função para exibir mensagens de alerta
      function showAlert(message, type = 'success') {
          // Remove alertas anteriores
          const existingAlert = document.getElementById('formAlert');
          if (existingAlert) {
              existingAlert.remove();
          }
          
          const colors = {
              success: 'bg-green-100 border-green-400 text-green-700',
              error: 'bg-red-100 border-red-400 text-red-700',
              warning: 'bg-yellow-100 border-yellow-400 text-yellow-700'
          };
          
          const alertDiv = document.createElement('div');
          alertDiv.id = 'formAlert';
          alertDiv.className = `${colors[type]} border px-4 py-3 rounded relative mb-4`;
          alertDiv.role = 'alert';
          
          alertDiv.innerHTML = `
              <strong class="font-bold">${type === 'success' ? 'Sucesso!' : 'Erro!'}</strong>
              <span class="block sm:inline">${message}</span>
              <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                  <svg class="fill-current h-6 w-6" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                      <title>Fechar</title>
                      <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                  </svg>
              </span>
          `;
          
          // Adiciona o alerta antes do formulário
          const form = document.getElementById('storeCreationForm');
          form.parentNode.insertBefore(alertDiv, form);
          
          // Fecha o alerta ao clicar no X
          alertDiv.querySelector('svg').addEventListener('click', () => {
              alertDiv.remove();
          });
          
          // Remove automaticamente após 5 segundos
          if (type !== 'error') {
              setTimeout(() => {
                  alertDiv.remove();
              }, 5000);
          }
      }
  </script>
</body>
</html>