<div class="px-8">
    <input type="text" class="p-4 rounded-md w-full border-gray-200 shadow-sm" 
            placeholder="Buscar producto por codigo o descripcion" 
            wire:model="filter"
            wire:keydown.debounce.500ms="filterProductsHandler()"
            >
</div>
