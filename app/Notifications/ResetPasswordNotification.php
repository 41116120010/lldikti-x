<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    /**
     * The password reset token.
     */
    public string $token;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $expireMinutes = (int) config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('[SIPERAPAT] Permintaan Atur Ulang Kata Sandi Akun Pegawai')
            ->greeting('Yth. Bapak/Ibu ' . ($notifiable->name ?? 'Pegawai LLDIKTI') . ',')
            ->line('Kami menerima permohonan untuk mengatur ulang kata sandi akun SIPERAPAT (Sistem Informasi Pencatatan Kehadiran Rapat) Anda di lingkungan LLDIKTI Wilayah X Kemendiktisaintek.')
            ->action('Atur Ulang Kata Sandi', $resetUrl)
            ->line('Tautan pemulihan kata sandi ini hanya berlaku selama ' . $expireMinutes . ' menit demi alasan keamanan data kedinasan.')
            ->line('Jika Anda tidak merasa melakukan permohonan ini, abaikan email ini. Kata sandi akun Anda tidak akan berubah dan akun Anda tetap aman.')
            ->salutation('Hormat kami,' . "\n" . 'Tim Pengelola SIPERAPAT LLDIKTI Wilayah X');
    }
}
