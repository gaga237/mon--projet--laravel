<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Retourne tous les utilisateurs sauf celui actuellement connecté
        $users = User::where('id', '!=', $request->user()->id)
            ->orderBy('name')
            ->get();
            
        return response()->json($users);
    }
}
