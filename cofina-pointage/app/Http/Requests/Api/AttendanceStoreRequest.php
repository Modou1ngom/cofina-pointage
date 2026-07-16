<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\Concerns\MergesMobileRequestKeys;
use App\Support\PointageQrScanUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceStoreRequest extends FormRequest
{
    use MergesMobileRequestKeys;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeSnakeFromCamelPairs([
            ['qr_payload', 'qrPayload'],
            ['biometric_nonce', 'biometricNonce'],
        ]);

        $raw = (string) ($this->input('qr_payload') ?? '');
        if ($raw !== '') {
            $this->merge([
                'qr_payload' => PointageQrScanUrl::normalizeScannedContent($raw),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'qr_payload' => ['required', 'string', 'min:8', 'max:512'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'biometric_nonce' => ['nullable', 'string', 'max:2048'],
            'type' => ['nullable', 'string', Rule::in(['checkin', 'checkout', 'arrivee', 'depart'])],
        ];
    }
}
