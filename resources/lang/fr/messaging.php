<?php

declare(strict_types=1);

return [
    'otp' => [
        'message' => 'Votre code de vérification est: :code. Valide pendant :expiry minutes.',
        'expired' => 'Ce code a expiré.',
        'invalid' => 'Code invalide.',
        'max_attempts' => 'Nombre maximum de tentatives atteint.',
    ],

    'errors' => [
        'send_failed' => 'Échec de l\'envoi du message.',
        'invalid_recipient' => 'Numéro de téléphone invalide.',
        'quota_exceeded' => 'Quota de messages épuisé.',
        'configuration_missing' => 'Configuration manquante.',
        'api_error' => 'Erreur API.',
        'unsupported_operation' => 'Opération non supportée.',
    ],

    'success' => [
        'message_sent' => 'Message envoyé avec succès.',
        'otp_sent' => 'Code de vérification envoyé.',
        'otp_verified' => 'Code vérifié avec succès.',
    ],
];
