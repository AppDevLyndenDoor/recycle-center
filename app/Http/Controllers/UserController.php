<?php

namespace App\Http\Controllers;

class UserController extends Controller
{
    public function checkUserPermissions()
    {
        $user = auth()->user();
        if ($user->hasRole('admin')) {
            return 'admin';
        }
        else if ($user->hasRole('operator')) {
            return 'operator';
        }
        return 'anonymous';
    }
}
