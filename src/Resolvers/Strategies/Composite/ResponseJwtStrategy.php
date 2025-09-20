<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Strategies\Composite;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Resolvers\Strategies\AbstractStrategy;
use DemonDextralHorn\Resolvers\Strategies\Atomic\AffixStrategy;
use DemonDextralHorn\Resolvers\Strategies\Atomic\FromResponseBodyStrategy;

/**
 * [Composite]: Extracts a JWT token from the response body and prefixes it with "Bearer ".
 *
 * @class ResponseJwtStrategy
 */
final readonly class ResponseJwtStrategy extends AbstractStrategy
{
    public const BEARER_PREFIX = 'Bearer ';

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
                'strategy' => FromResponseBodyStrategy::class,
                'options' => $options,
            ],
            [
                'strategy' => AffixStrategy::class,
                'options' => ['prefix' => self::BEARER_PREFIX],
            ],
        ];

        return $this->chainExecutor($chain, $requestData, $responseData);
    }
}
