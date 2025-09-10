<?php

declare(strict_types=1);

namespace Tests\Cache\Identifiers;

use DemonDextralHorn\Cache\Identifiers\GuestUserIdentifier;
use DemonDextralHorn\Data\TargetRouteData;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Illuminate\Http\Request;

#[CoversClass(GuestUserIdentifier::class)]
final class GuestUserIdentifierTest extends TestCase
{
    private GuestUserIdentifier $guestUserIdentifier;

    public function setUp(): void
    {
        parent::setUp();

        $this->guestUserIdentifier = app(GuestUserIdentifier::class);
    }

    #[Test]
    public function it_gets_identifier_for_guest_user(): void
    {
        /* SETUP */
        $targetRouteData = new TargetRouteData(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: ['id' => 2],
            queryParams: ['page' => 1],
            headers: [],
            cookies: []
        );

        /* EXECUTE */
        $identifier = $this->guestUserIdentifier->getIdentifierFor($targetRouteData);

        /* ASSERT */
        $this->assertSame($this->guestUserIdentifier::GUEST_USER, $identifier);
    }
}