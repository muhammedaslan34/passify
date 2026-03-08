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

        return redirect('passify-extension://auth?token=' . urlencode($token));
    }
}
