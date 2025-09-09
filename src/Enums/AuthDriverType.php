<?php

declare(strict_types=1);

namespace DemonDextralHorn\Enums;

/**
 * Enum representing the types of authentication drivers.
 * Represents laravel config('auth.guards.api.driver').
 */
enum AuthDriverType: string
{
    case SESSION = 'session';
    case JWT = 'jwt';
}
