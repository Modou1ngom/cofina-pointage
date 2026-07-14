<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\Concerns\MergesMobileRequestKeys;
use App\Support\PointageQrScanUrl;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceScanRequest extends FormRequest
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
            ['qr_token', 'qrToken'],
        ]);

        $raw = (string) ($this->input('qr_payload') ?? $this->input('qr_token') ?? '');
        if ($raw !== '') {
            $normalized = PointageQrScanUrl::normalizeScannedContent($raw);
            $this->merge([
                'qr_payload' => $normalized,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'qr_payload' => ['required', 'string', 'min:8', 'max:2048'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function qrContent(): string
    {
        return (string) $this->validated('qr_payload');
    }
}
