<?php

declare(strict_types=1);

namespace DemonDextralHorn\Cache\Identifiers;

use DemonDextralHorn\Cache\Identifiers\Contracts\UserIdentifierInterface;
use DemonDextralHorn\Data\TargetRouteData;

/**
 * User identifier for guest users (not logged in or auth_driver is neither session nor jwt).
 * 
 * @class GuestUserIdentifier
 */
final class GuestUserIdentifier implements UserIdentifierInterface
{
    /**
     * {@inheritDoc}
     */
    public function getIdentifierFor(TargetRouteData $targetRouteData): string
    {
        return self::GUEST_USER;
    }
}