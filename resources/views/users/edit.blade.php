<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Users') }}
        </h2>
    </x-slot>
    
    <div class="flex flex-col py-8 max-w-2xl mx-auto">
        <form action="{{ route('users.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="flex flex-col max-w-2xl mx-auto">
                <div class="mb-8">
                    <label class="w-full text-gray-700" for="name">Nombre:</label>
                    <input type="text" class="w-full h-12 rounded-lg" name="name" value="{{$user->first_name}} {{$user->last_name}}" disabled="disabled">
                </div>
            </div>
            
            <div class="mb-8" x-data="{ showPassword: false }">
                <div class="mb-8">
                    <label class="w-full text-gray-700" for="subject">Email:</label>
                    <input type="email" class="w-full h-12 rounded-lg" name="email" value="{{$user->email}}"  disabled="disabled">
                </div>
            </div>
            
            <div class="mb-8 flex  justify-end" >
                <button class="bg-gray-700 text-center text-white font-semibold rounded-md px-12 py-2">Guardar</button>
            </div>
        </div>
        </form>
    </div>
        
</x-app-layout>
