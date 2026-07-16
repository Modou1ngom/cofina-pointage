<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\Concerns\MergesMobileRequestKeys;
use Illuminate\Foundation\Http\FormRequest;

class RegisterDeviceRequest extends FormRequest
{
    use MergesMobileRequestKeys;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeSnakeFromCamelPairs([
            ['device_id', 'deviceId'],
            ['serial_number', 'serialNumber'],
            ['os_version', 'osVersion'],
            ['app_version', 'appVersion'],
        ]);

        if ($this->filled('device_id')) {
            $this->merge([
                'device_id' => \App\Support\MobileDeviceId::normalize((string) $this->input('device_id')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string', 'max:128'],
            'serial_number' => ['nullable', 'string', 'max:128'],
            'model' => ['nullable', 'string', 'max:255'],
            'os_version' => ['nullable', 'string', 'max:100'],
            'app_version' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'device_id.required' => 'Identifiant appareil manquant.',
            'device_id.max' => 'Identifiant appareil trop long pour être enregistré.',
            'model.max' => 'Le modèle indiqué est trop long.',
            'os_version.max' => 'La version système indiquée est trop longue.',
            'app_version.max' => 'La version de l’application indiquée est trop longue.',
        ];
    }
}
