<?php

namespace App\Http\Controllers\Api\Pointrust;

use App\Http\Controllers\Controller;
use App\Models\PointrustAppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = PointrustAppNotification::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn (PointrustAppNotification $n) => [
                'id' => (string) $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'message' => $n->body,
                'created_at' => $n->created_at?->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
                'read' => (bool) $n->read,
            ]);

        return response()->json([
            'data' => $items,
        ]);
    }
}
