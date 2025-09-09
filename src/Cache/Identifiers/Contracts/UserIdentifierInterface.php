<?php

declare(strict_types=1);

namespace DemonDextralHorn\Cache\Identifiers\Contracts;

use DemonDextralHorn\Data\TargetRouteData;

/**
 * Interface for user identifier.
 *
 * @interface UserIdentifierInterface
 */
interface UserIdentifierInterface
{
    // Default constant for guest user identifier, It means user is not logged in regardless of auth driver (session, jwt, etc)
    public const GUEST_USER = 'guest';

    // sha256 is cryptographic hash, suitable for sensitive data like JWT tokens, session cookies, etc
    public const HASH_ALGORITHM = 'sha256';

    /**
     * Get a unique identifier for the user based on the target route data (request data).
     * 
     * @param TargetRouteData $targetRouteData
     * 
     * @return string
     */
    public function getIdentifierFor(TargetRouteData $targetRouteData): string;
}