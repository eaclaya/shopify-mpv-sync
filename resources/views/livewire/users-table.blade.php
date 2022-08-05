<div class="w-full" >
@if (isset($users) === true && count($users) > 0)
    <div class="flex flex-col">
        <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-4 py-2">
                            Email
                        </th>
                        <th class="text-left px-4 py-2">
                            Name
                        </th>
                        <th class="text-left px-4 py-2">
                            Token
                        </th>
                        <th class="text-left px-4 py-2">
                            Rol
                        </th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($users as $user)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{$user['email']}}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{$user['first_name']}}  {{$user['last_name']}}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{$user['token']}}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{$user['is_admin'] ? 'Admin' : 'Vendedor'}}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                           <a href="{{route('users.edit', $user['id'])}}" class="text-indigo-500">View/Edit</a>
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
            <h2 class="text-center text-gray-500 text-2xl">No hay useros</h2>
        </div>
    </div>
@endif
</div>