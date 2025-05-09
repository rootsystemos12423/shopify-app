<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased">

        <div class="min-h-screen">

            <!-- HEADER -->
            <div class="absolute top-0 left-0 right-0 z-50 bg-[#1a1a1a] text-white shadow-sm">
                <div class="flex items-center h-16 px-4">
                  <!-- Logo Shopify -->
                  <div class="flex items-center mr-8">
                    <div class="flex items-center">
                      <img src="https://cdn.shopify.com/shopifycloud/web/assets/v1/vite/client/en/assets/shopify-glyph-white-DZNyE9BvHIk-.svg" 
                           alt="Shopify" class="h-6 mr-2">
                      <img src="https://cdn.shopify.com/shopifycloud/web/assets/v1/vite/client/en/assets/shopify-wordmark-monochrome-CpVsfBAAmxEP.svg" 
                           alt="Shopify" class="h-5 hidden md:block">
                    </div>
                  </div>
              
                  <!-- Menu direito -->
                  <div class="flex items-center ml-auto space-x-2">
                    <!-- Notificações -->
                    <button class="relative p-2 rounded-md hover:bg-gray-800 focus:outline-none">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                      </svg>
                      <span class="absolute top-0 right-0 w-4 h-4 bg-red-500 text-xs rounded-full">10</span>
                    </button>
              
                    <!-- Avatar/Conta -->
                    <button class="flex items-center p-1 space-x-2 rounded-md hover:bg-gray-800 focus:outline-none">
                      <div class="relative flex items-center justify-center w-8 h-8 bg-green-400 rounded-md">
                        <span class="text-sm text-[#1a1a1a] font-medium">MS</span>
                      </div>
                      <span class="hidden text-sm font-bold md:block">My Store</span>
                    </button>
                  </div>
                </div>
              </div>


            <div class="flex h-screen bg-gray-100 mt-16">
                <!-- Sidebar -->
<div class="w-64 bg-white border-r border-gray-200">
    <nav class="p-4">
        <ul class="space-y-1">
            <!-- Home -->
            <li>
                <a href="#" class="flex items-center p-2 text-gray-700 hover:bg-gray-100 rounded-md group">
                    <span class="w-5 mr-3 text-gray-500 group-hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </span>
                    Home
                </a>
            </li>

            <!-- Orders -->
            <li>
                <a href="#" class="flex items-center justify-between p-2 text-gray-700 hover:bg-gray-100 rounded-md group">
                    <div class="flex items-center">
                        <span class="w-5 mr-3 text-gray-500 group-hover:text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </span>
                        Orders
                    </div>
                    <span class="px-2 py-1 text-xs font-medium text-text-800 bg-gray-100 rounded-full">3</span>
                </a>
            </li>

            <!-- Products -->
            <li>
                <a href="#" class="flex items-center p-2 text-gray-700 hover:bg-gray-100 rounded-md group">
                    <span class="w-5 mr-3 text-gray-500 group-hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </span>
                    Products
                </a>
            </li>

            <!-- Customers -->
            <li>
                <a href="#" class="flex items-center p-2 text-gray-700 hover:bg-gray-100 rounded-md group">
                    <span class="w-5 mr-3 text-gray-500 group-hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </span>
                    Customers
                </a>
            </li>

            <!-- Divider -->
            <li class="pt-2 mt-2 border-t border-gray-200"></li>

            <!-- Sales Channels -->
            <li>
                <p class="px-2 pt-4 pb-1 text-xs font-semibold text-gray-500 uppercase">Sales channels</p>
            </li>

            <!-- Online Store -->
            <li>
                <a href="#" class="flex items-center p-2 text-gray-700 hover:bg-gray-100 rounded-md group">
                    <span class="w-5 mr-3 text-gray-500 group-hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                        </svg>
                    </span>
                    Online Store
                </a>
            </li>

            <!-- Themes -->
            <li>
                <a href="#" class="flex items-center p-2 text-gray-700 hover:bg-gray-100 rounded-md group">
                    <span class="w-5 mr-3 text-gray-500 group-hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                        </svg>
                    </span>
                    Themes
                </a>
            </li>

            <!-- Divider -->
            <li class="pt-2 mt-2 border-t border-gray-200"></li>

            <!-- Apps -->
            <li>
                <a href="#" class="flex items-center p-2 text-gray-700 hover:bg-gray-100 rounded-md group">
                    <span class="w-5 mr-3 text-gray-500 group-hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </span>
                    Apps
                </a>
            </li>
        </ul>
    </nav>
</div>

            <!-- Page Content -->
            <main class="w-full">
                {{ $slot }}
            </main>
        </div>

        @stack('modals')

        @livewireScripts
    </body>
</html>
