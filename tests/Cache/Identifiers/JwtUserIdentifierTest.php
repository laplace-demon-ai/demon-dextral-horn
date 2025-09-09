<?php

declare(strict_types=1);

namespace Tests\Cache\Identifiers;

use DemonDextralHorn\Cache\Identifiers\JwtUserIdentifier;
use DemonDextralHorn\Data\TargetRouteData;
use DemonDextralHorn\Enums\AuthDriverType;
use DemonDextralHorn\Enums\HttpHeaderType;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Http\Request;

/**
 * Test class for JwtUserIdentifier.
 * 
 * @class JwtUserIdentifierTest
 */
final class JwtUserIdentifierTest extends TestCase
{
    private JwtUserIdentifier $jwtUserIdentifier;

    public function setUp(): void
    {
        parent::setUp();

        $this->jwtUserIdentifier = app(JwtUserIdentifier::class);
    }

    #[Test]
    public function it_gets_identifier_for_jwt_user(): void
    {
        /* SETUP */
        $targetRouteData = new TargetRouteData(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: ['id' => 2],
            queryParams: ['page' => 1],
            headers: [
                HttpHeaderType::AUTHORIZATION->value => 'Bearer sample.jwt.token'
            ],
            cookies: []
        );

        /* EXECUTE */
        $identifier = $this->jwtUserIdentifier->getIdentifierFor($targetRouteData);

        /* ASSERT */
        $this->assertStringContainsString(AuthDriverType::JWT->value, $identifier);
    }

    #[Test]
    public function it_gets_identifier_for_guest_user_when_no_jwt_token(): void
    {
        /* SETUP */
        $targetRouteData = new TargetRouteData(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: ['id' => 2],
            queryParams: ['page' => 1],
            headers: [
                // No Authorization header
            ],
            cookies: []
        );

        /* EXECUTE */
        $identifier = $this->jwtUserIdentifier->getIdentifierFor($targetRouteData);

        /* ASSERT */
        $this->assertSame($this->jwtUserIdentifier::GUEST_USER, $identifier);
    }
}