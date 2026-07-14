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
}
