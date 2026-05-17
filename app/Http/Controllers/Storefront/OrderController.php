<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Themes\ThemeRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function show(Request $request): View
    {
        $orderNumber = $request->route('orderNumber');
        $tenant = app('current_tenant');
        $store = $tenant->store;
        $theme = ThemeRegistry::exists($store->theme) ? $store->theme : 'default';

        $order = Order::where('tenant_id', $tenant->id)
            ->where('order_number', $orderNumber)
            ->with('items')
            ->firstOrFail();

        return view('storefront.order', [
            'tenant' => $tenant,
            'store' => $store,
            'theme' => $theme,
            'order' => $order,
        ]);
    }
}
