<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class CredentialController extends Controller
{
    public function index()
    {
        return response()->json([]);
    }

    public function store()
    {
        return response()->json([], 201);
    }

    public function update()
    {
        return response()->json([]);
    }

    public function destroy()
    {
        return response()->json([], 204);
    }

    public function search()
    {
        return response()->json([]);
    }
}
