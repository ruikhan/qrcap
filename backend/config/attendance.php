<?php

//App-specific attendance policy defaults. Sessions may override these per-session.
return [
    'present_grace_minutes' => (int) env('ATTENDANCE_PRESENT_GRACE_MINUTES', 10),
    'qr_rotation_seconds' => (int) env('ATTENDANCE_QR_ROTATION_SECONDS', 30),
    'qr_expiry_seconds' => (int) env('ATTENDANCE_QR_EXPIRY_SECONDS', 60),
    'admin_seed_email' => env('ADMIN_SEED_EMAIL', 'admin@qrcap.test'),
    'admin_seed_password' => env('ADMIN_SEED_PASSWORD'),
];