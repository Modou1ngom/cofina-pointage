<?php

namespace App\Support;

use App\Models\Device;
use App\Models\User;

final class MobileDeviceRegistration
{
    /**
     * Enregistre l’appareil et, à la première connexion, persiste le numéro de série dans users.avatar_url.
     *
     * @param  array{device_id: string, serial_number?: string|null, model?: string|null, os_version?: string|null, app_version?: string|null}  $validated
     */
    public static function register(User $user, array $validated): Device
    {
        $deviceId = trim($validated['device_id']);
        $serial = trim((string) ($validated['serial_number'] ?? ''));

        if ($serial !== '' && MobileDeviceSerialResolver::isPlausibleDeviceSerial($serial)) {
            if (! MobileDeviceSerialResolver::isPlausibleDeviceSerial($deviceId)) {
                $deviceId = $serial;
            }
        } elseif (MobileDeviceSerialResolver::isPlausibleDeviceSerial($deviceId)) {
            $serial = $deviceId;
        } else {
            $serial = '';
        }

        $device = Device::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'device_id' => $deviceId,
            ],
            [
                'serial_number' => $serial,
                'model' => $validated['model'] ?? null,
                'os_version' => $validated['os_version'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
            ]
        );

        if ($serial !== '' && MobileDeviceSerialResolver::isPlausibleDeviceSerial($serial)) {
            $storedAvatar = trim((string) ($user->avatar_url ?? ''));
            if ($storedAvatar === '' || ! MobileDeviceSerialResolver::isPlausibleDeviceSerial($storedAvatar)) {
                $user->forceFill(['avatar_url' => $serial])->saveQuietly();
            }
        }

        return $device;
    }
}
