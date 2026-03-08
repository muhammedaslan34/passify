<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExtensionAuthController extends Controller
{
    public function show()
    {
        return view('extension-auth');
    }

    public function connect(Request $request)
    {
        $token = $request->user()->createToken('chrome-extension')->plainTextToken;

        return view('extension-auth-success', ['token' => $token]);
    }
}
