<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class OrganizationController extends Controller
{
    public function index()
    {
        return response()->json([]);
    }

    public function store()
    {
        return response()->json([], 201);
    }

    public function show()
    {
        return response()->json([]);
    }
}
