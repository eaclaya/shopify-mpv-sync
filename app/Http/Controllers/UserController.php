<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request){
        $users = User::all();
        return view('users.index', compact('users'));
    }

    public function edit(User $user){
        return view('users.edit', compact('user'));
    }
    
    public function update(User $user, Request $request){
        $token = $request->user()->createToken($user->email);
        $plainToken = $token->plainTextToken;
        request()->session()->flash('success', "Token created successfully: {$plainToken}");
        return redirect()->route('users.index');
    }
}
