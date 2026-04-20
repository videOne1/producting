<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Returns the counts of users, products, and orders in the database
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {

        $cachedData = Cache::get('dashboard_stats');

        if($cachedData) {
            return response()->json($cachedData);
        }
        
        $usersCount = User::count();
        $productsCount = Product::count();
        $ordersCount = Order::count();

        $returnData = [
            'total_users' => $usersCount,
            'total_products' => $productsCount,
            'total_orders' => $ordersCount
        ];

        Cache::put('dashboard_stats', $returnData, now()->addMinutes(10));

        return response()->json($returnData);
    }
}
