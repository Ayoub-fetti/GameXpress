<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashbordController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'total_products' => Product::count(),
            'total_categories' => Category::count(),
            'total_users' => User::count(),
            'out_of_stock_products' => Product::where('stock', 0)->count(),
            'latest_products' => Product::latest()->take(5)->get(),
        ]);
    }
}
