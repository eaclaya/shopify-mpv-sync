<?php

namespace App\Http\Livewire;

use Livewire\Component;

class ProductSearch extends Component
{
    public $filter = '';

    public function filterProductsHandler()
    {
        $this->emit('filterProducts', $this->filter);
    }
    public function render()
    {
        return view('livewire.product-search');
    }
}
