<?php

return [

    /**
     * Secret HS256 (min. 32 caractères recommandé). Définir POINTRUST_JWT_SECRET en production.
     */
    'jwt_secret' => env('POINTRUST_JWT_SECRET') ?: hash('sha256', (string) config('app.key'), false),

    'access_ttl_seconds' => (int) env('POINTRUST_ACCESS_TTL', 86400),

    'refresh_ttl_seconds' => (int) env('POINTRUST_REFRESH_TTL', 2592000),

    /** Si true : après mot de passe valide, pas de token — envoi OTP puis POST /verify-otp. */
    'login_requires_otp' => filter_var(env('POINTRUST_LOGIN_REQUIRES_OTP', false), FILTER_VALIDATE_BOOLEAN),

    /** Durée de vie du QR mobile (secondes), max 60 recommandé. */
    'qr_ttl_seconds' => min(120, max(15, (int) env('POINTRUST_QR_TTL', 60))),

    /** Désactiver POST /register-device (réponse 404 comme « non implémenté »). */
    'device_register_enabled' => filter_var(env('POINTRUST_DEVICE_REGISTER', true), FILTER_VALIDATE_BOOLEAN),

    /** Durée OTP login mobile (secondes). */
    'otp_ttl_seconds' => (int) env('POINTRUST_OTP_TTL', 300),

    /**
     * Réponse POST /api/login : inclure debug_otp (code en clair). Jamais en production.
     * POINTRUST_DEBUG_OTP_IN_LOGIN=true|false (défaut : true si APP_ENV=local).
     */
    'debug_otp_in_login_response' => env('POINTRUST_DEBUG_OTP_IN_LOGIN') !== null
        ? filter_var(env('POINTRUST_DEBUG_OTP_IN_LOGIN'), FILTER_VALIDATE_BOOLEAN)
        : env('APP_ENV') === 'local',

    /**
     * Page WebView : GET /mobile/pointrust/login (HTML + appels /api/login).
     * Désactiver (404) si vous n’utilisez pas ce flux.
     */
    'mobile_web_login_enabled' => filter_var(env('POINTRUST_MOBILE_WEB_LOGIN', true), FILTER_VALIDATE_BOOLEAN),

    /**
     * Schéma URL optionnel après succès (ex. pointrust) : redirection pointrust://auth?payload=...
     * Préférez une interface JS native (PointrustNative, ReactNativeWebView, Android).
     */
    'mobile_native_scheme' => env('POINTRUST_MOBILE_NATIVE_SCHEME'),
];
