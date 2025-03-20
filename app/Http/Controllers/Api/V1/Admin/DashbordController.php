<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Order;
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
    public function listTransactions()
    {
        $payments = Payment::all();
        return response()->json($payments);
    }

    public function updateOrderStatus(Request $request, $orderId): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,cancelled,shipped', // Ajoutez les statuts valides ici
        ]);
    
        $order = Order::find($orderId);
    
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
    
        $order->update(['status' => $request->status]);
    
        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order,
        ]);
    }

}
