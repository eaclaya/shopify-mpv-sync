<?php

namespace App\Http\Livewire;

use Livewire\Component;

class UserSearch extends Component
{
    public $filter = '';

    public function filterUsersHandler()
    {
        $this->emit('filterUsers', $this->filter);
    }
    public function render()
    {
        return view('livewire.user-search');
    }
}
