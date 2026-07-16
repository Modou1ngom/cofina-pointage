<?php

namespace App\Http\Controllers\Api\Pointrust;

use App\Http\Controllers\Controller;
use App\Models\PointrustDevice;
use App\Support\MobileDeviceId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (! config('pointrust.device_register_enabled')) {
            abort(404);
        }

        $deviceId = MobileDeviceId::normalize(
            (string) ($request->input('device_id') ?? $request->input('deviceId') ?? '')
        );
        $request->merge(['device_id' => $deviceId]);

        $validated = $request->validate([
            'device_id' => 'required|string|max:128',
            'model' => 'nullable|string|max:255',
            'os_version' => 'nullable|string|max:100',
            'app_version' => 'nullable|string|max:50',
        ], [
            'device_id.required' => 'Identifiant appareil manquant.',
            'device_id.max' => 'Identifiant appareil trop long pour être enregistré.',
        ]);

        $user = $request->user();
        PointrustDevice::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'device_id' => $validated['device_id'],
            ],
            [
                'model' => $validated['model'] ?? null,
                'os_version' => $validated['os_version'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
            ]
        );

        return response()->json(['message' => 'Appareil enregistré'], 201);
    }
}
