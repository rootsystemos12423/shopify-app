<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Escolher Loja</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
    .gradient-bg {
      background: radial-gradient(circle at 20% 30%, #3b0764, transparent 50%),
                  radial-gradient(circle at 80% 30%, #0f766e, transparent 50%),
                  #000;
    }
  </style>
  <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center px-4">

  <div class="w-full max-w-sm bg-white rounded-2xl shadow-2xl p-6 relative">

    <!-- Avatar do usuário no topo direito -->
    <div class="absolute top-4 right-4 w-8 h-8 rounded-full bg-blue-400 text-white text-xs font-bold flex items-center justify-center">
      {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
    </div>

    <!-- Logo Shopify -->
    <div class="mb-6">
      <img src="https://cdn.shopify.com/shopifycloud/web/assets/v1/vite/client/en/assets/shopify-logo-4-By2TuYD0Az6L.svg" alt="Shopify" class="h-5">
    </div>

    <h2 class="text-lg font-semibold text-gray-800 mb-6">Bem vindo de volta</h2>

    <div class="space-y-4">
      @foreach ($stores as $store)
        <button type="button"
          data-token="{{ $store->personal_token }}"
          class="select-store w-full flex items-center gap-4 p-4 rounded-lg border border-gray-200 hover:border-black transition hover:shadow-sm text-left">
          
          <div class="w-10 h-10 rounded-full bg-green-500 text-white flex items-center justify-center font-bold uppercase">
            {{ strtoupper(substr($store->name, 0, 2)) }}
          </div>

          <div class="flex-1">
            <div class="text-sm font-medium text-gray-900">{{ $store->name }}</div>
          </div>
        </button>
      @endforeach
    </div>

    <!-- Botão criar nova loja -->
    <div class="mt-6">
      <a href="{{ route('create.store') }}" class="inline-flex items-center gap-2 text-sm font-semibold px-3 py-2 bg-black text-white rounded-md hover:bg-gray-800 transition">
        <span class="text-lg leading-none">+</span> Create store
      </a>
    </div>

  </div>

  <script>
      document.querySelectorAll('.select-store').forEach(button => {
        button.addEventListener('click', () => {
          const storeToken = button.getAttribute('data-token');
          
          // Armazena nos dois tipos de storage
          sessionStorage.setItem('store_token', storeToken);
          
          window.location.href = "{{ route('dashboard') }}";
        });
      });
    </script>

</body>
</html>
