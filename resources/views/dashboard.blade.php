<x-app-layout>
        <!-- Conteúdo Principal -->
        <div class="flex-1 overflow-y-auto p-8">
            <!-- Cards de Métricas -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="text-gray-500 text-sm">Total de Produtos</div>
                    <div class="text-3xl font-bold mt-2">1,024</div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="text-gray-500 text-sm">Pedidos Hoje</div>
                    <div class="text-3xl font-bold mt-2">24</div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="text-gray-500 text-sm">Receita Mensal</div>
                    <div class="text-3xl font-bold mt-2">R$ 8,560</div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="text-gray-500 text-sm">Tema Ativo</div>
                    <div class="text-xl font-bold mt-2 truncate">Shopify Dawn</div>
                </div>
            </div>

            <!-- Seção de Temas -->
            <div class="bg-white p-6 rounded-lg shadow mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold">Meus Temas</h2>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Importar Tema
                    </button>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Tema 1 -->
                    <div class="border rounded-lg overflow-hidden">
                        <div class="h-32 bg-gray-200"></div>
                        <div class="p-4">
                            <h3 class="font-medium">Shopify Dawn</h3>
                            <p class="text-sm text-gray-500 mb-2">Ativo</p>
                            <div class="flex space-x-2">
                                <button class="text-sm text-blue-600 hover:underline">Personalizar</button>
                                <button class="text-sm text-gray-600 hover:underline">Duplicar</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tema 2 -->
                    <div class="border rounded-lg overflow-hidden">
                        <div class="h-32 bg-gray-300"></div>
                        <div class="p-4">
                            <h3 class="font-medium">Minimal</h3>
                            <p class="text-sm text-gray-500 mb-2">Inativo</p>
                            <div class="flex space-x-2">
                                <button class="text-sm text-blue-600 hover:underline">Ativar</button>
                                <button class="text-sm text-gray-600 hover:underline">Excluir</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Novo Tema -->
                    <div class="border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center hover:border-blue-500 transition">
                        <div class="p-6 text-center">
                            <svg class="w-10 h-10 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            <p class="mt-2 text-sm text-gray-600">Adicionar Tema</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pedidos Recentes -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">Pedidos Recentes</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pedido</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#1001</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">João Silva</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">10/05/2023</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">R$ 249,90</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Pago</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#1000</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Maria Souza</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">09/05/2023</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">R$ 189,90</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Processando</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
</x-app-layout>