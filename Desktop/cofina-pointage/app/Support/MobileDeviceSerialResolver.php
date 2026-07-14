<?php

namespace App\Support;

use App\Models\Device;
use App\Models\User;
use Illuminate\Http\Request;

final class MobileDeviceSerialResolver
{
    public static function fromRequest(Request $request, User $user): ?string
    {
        $deviceKey = self::deviceKeyFromRequest($request);

        return self::forUser($user, $deviceKey);
    }

    public static function forUser(User $user, ?string $deviceKey = null): ?string
    {
        if ($deviceKey !== null && $deviceKey !== '' && self::isPlausibleDeviceSerial($deviceKey)) {
            $device = Device::query()
                ->where('user_id', $user->id)
                ->where(function ($q) use ($deviceKey) {
                    $q->where('device_id', $deviceKey)
                        ->orWhere('serial_number', $deviceKey);
                })
                ->orderByDesc('updated_at')
                ->first();

            if ($device !== null) {
                $fromDevice = self::serialFromDevice($device);
                if ($fromDevice !== null) {
                    return $fromDevice;
                }
            }
        }

        $stored = trim((string) ($user->avatar_url ?? ''));
        if (self::isPlausibleDeviceSerial($stored)) {
            return $stored;
        }

        return self::bestSerialFromDevices($user);
    }

    public static function deviceKeyFromRequest(Request $request): ?string
    {
        foreach (['X-Device-Serial', 'Device-Serial', 'X-Device-Id', 'Device-Id'] as $header) {
            $value = trim((string) $request->header($header, ''));
            if ($value !== '' && self::isPlausibleDeviceSerial($value)) {
                return $value;
            }
        }

        $serial = trim((string) ($request->input('serial_number') ?? $request->input('serialNumber') ?? ''));
        if ($serial !== '' && self::isPlausibleDeviceSerial($serial)) {
            return $serial;
        }

        $deviceId = trim((string) ($request->input('device_id') ?? $request->input('deviceId') ?? ''));
        if ($deviceId !== '' && self::isPlausibleDeviceSerial($deviceId)) {
            return $deviceId;
        }

        return null;
    }

    /**
     * Exclut les User-Agent Web (Flutter Web) et autres valeurs non exploitables comme n° de série.
     */
    public static function isPlausibleDeviceSerial(string $value): bool
    {
        $value = trim($value);
        if ($value === '' || strlen($value) > 128) {
            return false;
        }

        $lower = strtolower($value);
        foreach (['mozilla/', 'applewebkit', 'chrome/', 'safari/', 'edg/', 'windows nt', 'linux x86', 'android'] as $needle) {
            if (str_contains($lower, $needle)) {
                return false;
            }
        }

        if (preg_match('/\s{2,}/', $value)) {
            return false;
        }

        return true;
    }

    private static function bestSerialFromDevices(User $user): ?string
    {
        $devices = Device::query()
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->get();

        foreach ($devices as $device) {
            $serial = self::serialFromDevice($device);
            if ($serial !== null) {
                return $serial;
            }
        }

        return null;
    }

    private static function serialFromDevice(Device $device): ?string
    {
        $serial = trim((string) ($device->serial_number ?? ''));
        if (self::isPlausibleDeviceSerial($serial)) {
            return $serial;
        }

        $deviceId = trim((string) ($device->device_id ?? ''));
        if (self::isPlausibleDeviceSerial($deviceId)) {
            return $deviceId;
        }

        return null;
    }
}
