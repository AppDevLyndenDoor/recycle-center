<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): Response|RedirectResponse
    {
        if ($request instanceof Request && $request->wantsJson()) {
            return response()->noContent();
        }

        return redirect()->route('login');
    }
}
