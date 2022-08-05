<?php

namespace App\Http\Livewire;

use App\Models\User;
use Livewire\Component;

class UsersTable extends Component
{
     public $users;
    protected $listeners = ['filterUsers' => 'filterUsers'];


    public function filterUsers($filter = ''){
        $this->users = User::where('email', 'like', "%{$filter}%")->get();
    }
    
    public function mount($users){
        $this->users = $users;
    }
    public function render()
    {
        
        return view('livewire.users-table');
    }
}
