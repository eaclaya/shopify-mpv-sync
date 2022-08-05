<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Users') }}
        </h2>
    </x-slot>
    <div class="flex flex-col">
        @livewire('user-search')
    </div>
    <div class="flex flex-col py-8">
        @livewire('users-table', ['users' => $users], key('users-table'))
        
    </div>
        
</x-app-layout>
