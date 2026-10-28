<?php

namespace Database\Factories;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static::ensureSampleAssets();

        return [
            'agenda_id' => Agenda::factory(),
            'user_id' => fn () => User::query()->inRandomOrder()->first()?->id ?? User::factory(),
            'signed_at' => now(),
            'selfie_path' => 'attendances/seeded/sample-selfie.jpg',
            'signature_path' => 'attendances/seeded/sample-signature.png',
            'ip_address' => '10.10.' . mt_rand(1, 10) . '.' . mt_rand(10, 250),
            'user_agent' => \Illuminate\Support\Arr::random([
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
                'Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.6778.200 Mobile Safari/537.36',
            ]),
        ];
    }

    /**
     * Pastikan berkas sampel selfie (JPEG) dan tanda tangan (PNG transparan) tersedia di storage/app/public
     * agar pratinjau maupun ekspor dokumen PDF/Word tidak mengalami missing file.
     */
    public static function ensureSampleAssets(): void
    {
        $disk = Storage::disk('public');

        if (! $disk->exists('attendances/seeded/sample-selfie.jpg')) {
            $minimalJpeg = base64_decode(
                '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA='
            );
            $disk->put('attendances/seeded/sample-selfie.jpg', $minimalJpeg);
        }

        if (! $disk->exists('attendances/seeded/sample-signature.png')) {
            $minimalPng = base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
            );
            $disk->put('attendances/seeded/sample-signature.png', $minimalPng);
        }
    }
}
