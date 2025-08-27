<?php

declare(strict_types=1);

namespace DemonDextralHorn\Enums;

/**
 * Enum representing the types of ordering.
 */
enum OrderType: string
{
    case ASC = 'asc';
    case DESC = 'desc';
}
