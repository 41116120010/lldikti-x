<?php

namespace App\Http\Requests\Attendance;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $agenda = $this->route('agenda');
        return $this->user()?->can('checkIn', [Attendance::class, $agenda]) ?? false;
    }

    public function rules(): array
    {
        return [
            'selfie_data' => ['required_without:selfie_file', 'nullable', 'string', 'regex:/^data:image\/(jpeg|jpg|png|webp);base64,/i'],
            'selfie_file' => ['required_without:selfie_data', 'nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:1024'],
            'signature_data' => ['required_without:signature_file', 'nullable', 'string', 'regex:/^data:image\/(png|jpeg|jpg|webp);base64,/i'],
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
}
