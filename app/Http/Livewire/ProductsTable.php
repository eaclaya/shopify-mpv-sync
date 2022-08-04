<?php

namespace App\Http\Livewire;

use App\Models\Product;
use Livewire\Component;

class ProductsTable extends Component
{
    public $products;
    protected $listeners = ['filterProducts' => 'filterProducts'];


    public function filterProducts($filter = ''){
        $this->products = Product::sync()->where(function($query) use ($filter){
            $query->where('notes', 'like', "%{$filter}%")
            ->Orwhere('product_key', $filter);
        })->get();
    }
    
    public function mount($products){
        $this->products = $products;
    }
    public function render()
    {
        
        return view('livewire.products-table');
    }
}
