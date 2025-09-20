<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Strategies\Composite;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;
use DemonDextralHorn\Resolvers\Strategies\AbstractStrategy;
use DemonDextralHorn\Resolvers\Strategies\Atomic\FromRequestQueryStrategy;
use Illuminate\Support\Arr;

/**
 * [Composite]: Strategy that forwards a value from the request query parameters to another strategy.
 *
 * @class ForwardQueryParamStrategy
 */
final readonly class ForwardQueryParamStrategy extends AbstractStrategy
{
    /**
     * {@inheritdoc}
     */
    public function handle(
        ?RequestData $requestData = null,
        ?ResponseData $responseData = null,
        ?array $options = [],
        mixed $value = null
    ): mixed {
        $sourceKey = Arr::get($options, 'source_key');

        /*
         * Validate presence of source_key in query parameters. e.g. ?some_key=value.
         * Because we cannot forward something that does not exist.
         */
        if (! Arr::has($requestData?->queryParams ?? [], $sourceKey)) {
            throw new MissingStrategyOptionException(self::class, 'source_key');
        }

        $chain = [
            [
                'strategy' => FromRequestQueryStrategy::class,
                'options' => $options,
            ],
        ];

        return $this->chainExecutor($chain, $requestData, $responseData);
    }
}
