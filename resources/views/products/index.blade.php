<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Products') }}
        </h2>
    </x-slot>
    <div class="flex flex-col">
        @livewire('product-search')
    </div>
    <div class="flex flex-col py-8">
        @livewire('products-table', ['products' => $products], key('products-table'))
        
    </div>
        
</x-app-layout>
