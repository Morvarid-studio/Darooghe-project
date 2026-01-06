<?php

// جمع‌آوری allowed origins
$allowedOrigins = [
    // Development origins
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    'http://188.213.198.182:33495',
    'http://188.213.198.182:34595'
];

// اگر APP_URL تنظیم شده و localhost نیست، آن را هم اضافه کن
$appUrl = env('APP_URL', '');
if ($appUrl && !str_contains($appUrl, 'localhost') && !str_contains($appUrl, '127.0.0.1')) {
    $allowedOrigins[] = $appUrl;
    // اگر http است، https را هم اضافه کن و برعکس
    if (str_starts_with($appUrl, 'http://')) {
        $allowedOrigins[] = str_replace('http://', 'https://', $appUrl);
    } elseif (str_starts_with($appUrl, 'https://')) {
        $allowedOrigins[] = str_replace('https://', 'http://', $appUrl);
    }
}

// اگر متغیر محیطی اضافی تعریف شده، اضافه کن
$additionalOrigins = env('CORS_ALLOWED_ORIGINS', '');
if ($additionalOrigins) {
    $allowedOrigins = array_merge($allowedOrigins, explode(',', $additionalOrigins));
}

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_unique(array_filter($allowedOrigins)),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 86400,
    'supports_credentials' => false,
];
