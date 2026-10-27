<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CookieConsentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $consent = $request->validate([
            'version' => ['required', 'in:1'],
            'analytics' => ['required', 'boolean'],
            'marketing' => ['required', 'boolean'],
        ]);

        $request->session()->put('cookie_consent', [
            'version' => $consent['version'],
            'analytics' => (bool) $consent['analytics'],
            'marketing' => (bool) $consent['marketing'],
            'consented_at' => now()->toISOString(),
        ]);

        return response()->json(['saved' => true]);
    }
}
