<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireWarehousePassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $hash = (string) config('access.password_hash');
        $granted = $request->session()->get('warehouse_access');

        if ($hash === '' || ! is_string($granted) || ! hash_equals(hash('sha256', $hash), $granted)) {
            $request->session()->forget('warehouse_access');

            return redirect()->guest(route('access.login'))->withHeaders(['Cache-Control' => 'no-store, private']);
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
