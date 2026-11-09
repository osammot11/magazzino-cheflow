<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnlockWarehouseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AccessController extends Controller
{
    public function login(): Response
    {
        return response()->view('access.login')->header('Cache-Control', 'no-store, private');
    }

    public function unlock(UnlockWarehouseRequest $request): RedirectResponse
    {
        $key = 'warehouse-login:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['password' => 'Troppi tentativi. Riprova tra '.RateLimiter::availableIn($key).' secondi.']);
        }

        $hash = (string) config('access.password_hash');

        if ($hash === '' || ! Hash::check($request->validated('password'), $hash)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['password' => 'Password non corretta. Riprova.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('warehouse_access', hash('sha256', $hash));

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('access.login');
    }
}
