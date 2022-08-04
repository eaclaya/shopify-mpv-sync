<main>
    @if(isset($products))
    <ul class="flex flex-col">
        @foreach($products as $product)
            <li class="flex flex-col">
                <h1>{{ $product->name }}</h1>
                <p>{{ $product->description }}</p>
                <p>{{ $product->price }}</p>
            </li>
        @endforeach
    </ul>
    @endif
</main>