<?php

declare(strict_types=1);

namespace DemonDextralHorn\Enums;

/**
 * Enum representing the types of HTTP headers.
 */
enum HttpHeaderType: string
{
    case AUTHORIZATION = 'Authorization';
    case ACCEPT = 'Accept';
    case ACCEPT_LANGUAGE = 'Accept-Language';
}
