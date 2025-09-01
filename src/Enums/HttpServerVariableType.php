<?php

declare(strict_types=1);

namespace DemonDextralHorn\Enums;

/**
 * Enum representing the types of HTTP server variables.
 */
enum HttpServerVariableType: string
{
    case AUTHORIZATION = 'HTTP_AUTHORIZATION';
}