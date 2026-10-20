<?php

return [
    'timezone' => env('BOOKING_TIMEZONE', config('app.timezone')),
    'opening_time' => env('BOOKING_OPENING_TIME', '09:00'),
    'closing_time' => env('BOOKING_CLOSING_TIME', '18:00'),
    'slot_interval_minutes' => (int) env('BOOKING_SLOT_INTERVAL_MINUTES', 30),

    // Porcentaje del total que se pide como depósito a clientes nuevos.
    'deposit_percentage' => (int) env('BOOKING_DEPOSIT_PERCENTAGE', 30),
    // Minutos para subir el comprobante antes de liberar el horario.
    'proof_upload_minutes' => (int) env('BOOKING_PROOF_UPLOAD_MINUTES', 20),
    // Minutos que tiene el administrador para revisar un comprobante ya subido.
    'admin_review_minutes' => (int) env('BOOKING_ADMIN_REVIEW_MINUTES', 180),
    // Ventana en días para considerar un no-show como historial reciente.
    'no_show_lookback_days' => (int) env('BOOKING_NO_SHOW_LOOKBACK_DAYS', 180),

    'admin_email' => env('BOOKING_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS')),

    'proof' => [
        'disk' => 'local',
        'directory' => 'payment-proofs',
        'max_kilobytes' => (int) env('BOOKING_PROOF_MAX_KB', 5120),
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

    'bank' => [
        'bank_name' => env('BOOKING_BANK_NAME', 'Banco por definir'),
        'account_holder' => env('BOOKING_BANK_HOLDER', 'Nails Time'),
        'clabe' => env('BOOKING_BANK_CLABE', '000000000000000000'),
        'oxxo_card' => env('BOOKING_BANK_OXXO_CARD', ''),
    ],
];
