<?php

namespace App\Http\Controllers;

use App\Services\SystemNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAsRead(Request $request, SystemNotificationService $notifications): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['nullable', 'string'],
            'all' => ['nullable', 'boolean'],
        ]);

        $sessionKey = $this->sessionKey($request);
        $readSignatures = $request->session()->get($sessionKey, []);
        $currentItems = collect($notifications->forUser($request->user(), [])['items']);

        if ($data['all'] ?? false) {
            $readSignatures = array_merge($readSignatures, $currentItems->pluck('signature')->all());
        } elseif (! empty($data['key'])) {
            $signature = $currentItems->firstWhere('key', $data['key'])['signature'] ?? null;

            if ($signature) {
                $readSignatures[] = $signature;
            }
        }

        $request->session()->put($sessionKey, array_values(array_unique($readSignatures)));

        return back()->setStatusCode(303);
    }

    private function sessionKey(Request $request): string
    {
        return 'notifications_read.'.$request->user()->id;
    }
}
