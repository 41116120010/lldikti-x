<?php

namespace App\Http\Requests\Attendance;

use App\Models\Attendance;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $agenda = $this->route('agenda');
        return $this->user()?->can('checkIn', [Attendance::class, $agenda]) ?? false;
    }

    /**
     * Handle failed authorization attempt with descriptive, user-friendly exception messages.
     *
     * @throws AuthorizationException
     */
    protected function failedAuthorization(): void
    {
        $agenda = $this->route('agenda');
        $user = $this->user();

        $message = 'Anda tidak memiliki wewenang untuk melakukan presensi pada agenda rapat ini.';

        if ($agenda) {
            if ($agenda->status !== 'ongoing') {
                $message = 'Sesi presensi untuk agenda rapat ini tidak sedang dibuka.';
            } elseif (now()->gt($agenda->effective_end_at)) {
                $endTimeStr = $agenda->effective_end_at->translatedFormat('H:i');
                $message = "Batas waktu pengisian presensi untuk agenda rapat ini telah berakhir sejak {$endTimeStr} WIB.";
            } elseif ($user && ! $user->isAdministrator() && ! $agenda->isUserEligible($user)) {
                $message = 'Unit kerja Anda tidak termasuk dalam daftar undangan agenda rapat ini.';
            } elseif ($user && $agenda->hasUserAttended($user)) {
                $message = 'Presensi Anda pada agenda rapat ini sudah pernah tercatat.';
            }
        }

        throw new AuthorizationException($message);
    }

    public function rules(): array
    {
        return [
            'selfie_data' => ['required_without:selfie_file', 'nullable', 'string', 'max:1000000', 'regex:/^data:image\/(jpeg|jpg|png|webp);base64,/i'],
            'selfie_file' => ['required_without:selfie_data', 'nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:512'],
            'signature_data' => ['required_without:signature_file', 'nullable', 'string', 'max:1000000', 'regex:/^data:image\/(png|jpeg|jpg|webp);base64,/i'],
            'signature_file' => ['required_without:signature_data', 'nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:512'],
        ];
    }

    public function attributes(): array
    {
        return [
            'selfie_data' => 'Foto Wajah Selfie',
            'selfie_file' => 'Berkas Foto Wajah Selfie',
            'signature_data' => 'Tanda Tangan Digital',
            'signature_file' => 'Berkas Tanda Tangan Digital',
        ];
    }

    public function messages(): array
    {
        return [
            'selfie_data.required_without' => 'Foto wajah selfie wajib diambil atau diunggah.',
            'selfie_data.regex' => 'Format foto wajah selfie harus berupa data URI Base64 gambar (JPEG, PNG, atau WebP).',
            'selfie_data.max' => 'Ukuran data foto selfie melebihi batas wajar (maksimal 512 KB).',
            'selfie_file.max' => 'Ukuran berkas foto selfie maksimal 512 KB.',
            'signature_data.required_without' => 'Tanda tangan digital wajib dibubuhkan atau diunggah.',
            'signature_data.regex' => 'Format tanda tangan digital harus berupa data URI Base64 gambar (PNG, JPEG, atau WebP).',
            'signature_data.max' => 'Ukuran data tanda tangan melebihi batas wajar (maksimal 512 KB).',
            'signature_file.max' => 'Ukuran berkas tanda tangan digital maksimal 512 KB.',
        ];
    }
}
