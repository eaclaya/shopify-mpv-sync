<div class="w-full" >
@if (isset($products) === true && count($products) > 0)
    <div class="flex flex-col">
        <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-4 py-2">
                            Codigo
                        </th>
                        <th class="text-left px-4 py-2">
                            Descripcion
                        </th>
                        <th class="text-left px-4 py-2">
                            Cantidad
                        </th>
                        <th class="text-left px-4 py-2">
                            Precio
                        </th>
                        <th class="text-left px-4 py-2">
                            Status
                        </th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($products as $product)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{$product->product_key}}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{$product->notes}}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{$product->qty}}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{$product->price}}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{$product->shopify_product_id ? 'Sincronizado' : 'Pendiente'}}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @if($product->shopify_product_id)
                                <a href="{{$product->shopify_product_url}}" target="_blank" class="text-indigo-600 hover:text-indigo-900">
                                    Ver en Shopify
                                </a>
                            @else
                            <form action="{{route('products.update', $product->id)}}" method="POST">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="text-indigo-600 hover:text-indigo-900">
                                    Sincronizar
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="mx-auto">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg py-16">
            <h2 class="text-center text-gray-500 text-2xl">No hay productos</h2>
        </div>
    </div>
@endif
</div>