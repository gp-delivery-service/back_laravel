<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AppController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'success' => true
        ], 200);
    }
}
