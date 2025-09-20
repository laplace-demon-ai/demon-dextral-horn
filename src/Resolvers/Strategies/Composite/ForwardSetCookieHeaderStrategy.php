<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Strategies\Composite;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Resolvers\Strategies\AbstractStrategy;
use DemonDextralHorn\Resolvers\Strategies\Atomic\FromResponseHeaderStrategy;
use DemonDextralHorn\Resolvers\Strategies\Atomic\ParseSetCookieStrategy;

/**
 * [Composite]: Strategy that forwards the 'Set-Cookie' header.
 * 
 * @class ForwardSetCookieHeaderStrategy
 */
final readonly class ForwardSetCookieHeaderStrategy extends AbstractStrategy
{
    public const SET_COOKIE_HEADER_NAME = 'setCookie';

    /**
     * {@inheritdoc}
     */
    public function handle(
        ?RequestData $requestData = null,
        ?ResponseData $responseData = null,
        ?array $options = [],
        mixed $value = null
    ): mixed {
        $chain = [
            [
                'strategy' => FromResponseHeaderStrategy::class,
                'options' => [
                    'header_name' => self::SET_COOKIE_HEADER_NAME,
                ],
            ],
            [
                'strategy' => ParseSetCookieStrategy::class,
                'options' => $options,
            ],
        ];

        return $this->chainExecutor($chain, $requestData, $responseData);
    }
}