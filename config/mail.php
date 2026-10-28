<?php

$rawScheme = strtolower(trim((string) (env('MAIL_SCHEME') ?: env('MAIL_ENCRYPTION', ''))));
$smtpPort = (int) env('MAIL_PORT', 587);
$normalizedScheme = match ($rawScheme) {
    'ssl', 'smtps' => 'smtps',
    'tls', 'starttls', 'smtp' => 'smtp',
    default => $smtpPort === 465 ? 'smtps' : 'smtp',
};

$defaultMailer = (string) env('MAIL_MAILER', 'log');
$mailPassword = trim((string) env('MAIL_PASSWORD', ''));
$brevoApiKey = trim((string) env('BREVO_API_KEY', ''));

// Jika admin mengisi API Key Brevo v3 (berawalan xkeysib-) pada MAIL_PASSWORD atau BREVO_API_KEY
// saat MAIL_MAILER=smtp, otomatis arahkan ke transport 'brevo' (HTTPS Port 443) agar tidak gagal
// autentikasi SMTP (535) dan bebas dari blokir port 587 pada VPS.
if ($defaultMailer === 'smtp' && (str_starts_with($mailPassword, 'xkeysib-') || str_starts_with($brevoApiKey, 'xkeysib-'))) {
    $defaultMailer = 'brevo';
}

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => $defaultMailer,

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "brevo", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => $normalizedScheme,
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', 'smtp-relay.brevo.com'),
            'port' => $smtpPort,
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => (int) env('MAIL_TIMEOUT', 15),
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'brevo' => [
            'transport' => 'brevo',
            'api_key' => $brevoApiKey !== '' ? $brevoApiKey : $mailPassword,
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Laravel')),
    ],

];
