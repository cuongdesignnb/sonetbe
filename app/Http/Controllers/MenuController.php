<?php

namespace App\Http\Controllers;

use App\Models\Menu;

class MenuController extends Controller
{
    /**
     * Public API: Get active menus as nested tree for frontend rendering
     */
    public function index()
    {
        return response()->json([
            'menus' => Menu::tree(true),
        ]);
    }
}
