<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonateController extends Controller
{
    public function stop(): RedirectResponse
    {
        $impersonatorId = Session::pull('impersonator_id');
        abort_unless($impersonatorId, 404);

        $original = User::find($impersonatorId);
        abort_unless($original && $original->hasRole('super_admin'), 403);

        Auth::login($original);

        return redirect()->to(config('app.url') . '/super');
    }
}
