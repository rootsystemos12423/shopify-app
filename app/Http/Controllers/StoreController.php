<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Store;
use App\Models\User;
use App\Models\Shopify;
use App\Models\Domain;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use App\Services\Shopify\ProductSyncService;
use App\Services\Shopify\DiscountSyncService;
use App\Services\Shopify\CustomerSyncService;
use App\Services\Shopify\OrderSyncService;
use App\Services\Shopify\UserSyncService;
use App\Services\Shopify\ThemeSyncService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;


class StoreController extends Controller
{
    public function show(){

        return view('store.create');
    }

    public function list(Request $request)
    {
        $stores = Store::where('user_id', auth()->id())->get();

        return view('store.list', compact('stores'));
    }

    public function credentials(){

        return view('store.credentials');
    }


    public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'domain' => [
            'required',
            'string',
            'max:255',
            'regex:/^[a-z0-9]+([\-\.]{1}[a-z0-9]+)*$/i',
            Rule::unique('domains', 'domain')->whereNull('deleted_at')
        ],
        'personal_token' => 'required',
    ]);

    $user = User::where('personal_token', $validated['personal_token'])->first();

    if(!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Not Authenticated.'
        ], 401);
    }

    DB::beginTransaction();

    try {
        // Gera um token único para a loja
        $storeToken = Str::random(60);
        
        // Cria a loja
        $store = Store::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'is_active' => true,
            'personal_token' => $storeToken, // Armazena o hash
            'settings' => [
                'created_at' => now()->toDateTimeString()
            ]
        ]);

        // Cria o domínio
        $domain = Domain::create([
            'store_id' => $store->id,
            'domain' => $validated['domain'],
            'is_primary' => true,
            'is_verified' => false
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Loja criada com sucesso!',
            'data' => [
                'store' => $store,
                'domain' => $domain,
                'admin_url' => env('APP_URL').'/dashboard/credentials',
                'access_token' => $store->personal_token, // Retorna apenas nesta resposta
                'expires_in' => 86400 // 24 horas em segundos
            ]
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        
        \Log::error("Erro ao criar loja: " . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Erro ao criar loja: ' . $e->getMessage()
        ], 500);
    }
}

public function integrations(Request $request)
    {
        try {
            // Validação dos dados
            $validated = $request->validate([
                'store_token' => 'required|string',
                'shopify_domain' => 'required|string|max:255',
                'api_key' => 'required|string|max:255',
                'api_secret' => 'required|string|max:255',
                'admin_token' => 'required|string|max:255',
                'webhook_secret' => 'nullable|string|max:255',
                'sync_options' => 'required|array',
                'sync_options.products' => 'sometimes|boolean',
                'sync_options.assets' => 'sometimes|boolean',
                'sync_options.discounts' => 'sometimes|boolean',
                'sync_options.customers' => 'sometimes|boolean',
                'sync_options.users' => 'sometimes|boolean',
                'sync_options.orders' => 'sometimes|boolean',
            ]);

            // Busca a loja pelo token
            $store = Store::where('personal_token', $validated['store_token'])->first();

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => 'Loja não encontrada'
                ], 404);
            }

            // Criptografa os dados sensíveis antes de salvar
            $integrationData = [
                'store_id' => $store->id,
                'shopify_domain' => $validated['shopify_domain'],
                'api_key' => $validated['api_key'],
                'api_secret' => $validated['api_secret'],
                'admin_token' => $validated['admin_token'],
                'webhook_secret' => $validated['webhook_secret'] ? $validated['webhook_secret'] : null,
            ];

            // Cria ou atualiza a integração
            $integration = Shopify::updateOrCreate(
                ['store_id' => $store->id],
                $integrationData
            );

            // Sincronizações
            $syncResults = [];
            
            $syncOptions = $validated['sync_options'];
            
            // Verifica cada opção de sincronização
            if (!empty($syncOptions['products'])) {
                $syncResults['products'] = app(ProductSyncService::class)->sync($integration);
            }

            if (!empty($syncOptions['discounts'])) {
                $syncResults['discounts'] = app(DiscountSyncService::class)->sync($integration);
            }

            if (!empty($syncOptions['customers'])) {
                $syncResults['customers'] = app(CustomerSyncService::class)->sync($integration);
            }

            if (!empty($syncOptions['orders'])) {
                $syncResults['orders'] = app(OrderSyncService::class)->sync($integration);
            }

            if (!empty($syncOptions['users'])) {
                $syncResults['users'] = app(UserSyncService::class)->sync($integration);
            }

            if (!empty($syncOptions['assets'])) {
                $syncResults['assets'] = app(ThemeSyncService::class)->sync($integration);
            }

            return response()->json([
                'success' => true,
                'message' => 'Integração configurada com sucesso',
                'data' => [
                    'integration_id' => $integration->id,
                    'sync_results' => $syncResults
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Shopify Integration Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao configurar integração',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
