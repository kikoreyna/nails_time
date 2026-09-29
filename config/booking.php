<?php

return [
    'timezone' => env('BOOKING_TIMEZONE', config('app.timezone')),
    'opening_time' => env('BOOKING_OPENING_TIME', '09:00'),
    'closing_time' => env('BOOKING_CLOSING_TIME', '18:00'),
    'slot_interval_minutes' => (int) env('BOOKING_SLOT_INTERVAL_MINUTES', 30),
];
