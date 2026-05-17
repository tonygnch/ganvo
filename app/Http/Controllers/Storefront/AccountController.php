<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Themes\ThemeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (! Auth::guard('customer')->check()) {
            return redirect('/account/login');
        }

        $customer = Auth::guard('customer')->user();
        $tenant = app('current_tenant');
        $store = $tenant->store;
        $theme = ThemeRegistry::exists($store->theme) ? $store->theme : 'default';

        $orders = $customer->orders()
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->limit(20)
            ->get();

        return view('storefront.account.index', compact('tenant', 'store', 'theme', 'customer', 'orders'));
    }
}
