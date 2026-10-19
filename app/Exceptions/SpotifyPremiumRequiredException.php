<?php

namespace App\Exceptions;

use RuntimeException;

class SpotifyPremiumRequiredException extends RuntimeException
{
    public const DEFAULT_MESSAGE = 'A reprodução completa exige uma Conta Spotify com assinatura Spotify Premium ativa.';

    public function __construct(string $message = self::DEFAULT_MESSAGE)
    {
        parent::__construct($message);
    }
}

