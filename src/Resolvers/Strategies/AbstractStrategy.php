<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Strategies;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Factories\StrategyFactory;
use DemonDextralHorn\Resolvers\Strategies\StrategyInterface;
use Illuminate\Support\Arr;

/**
 * Abstract class for strategies.
 *
 * @abstract AbstractStrategy
 */
abstract readonly class AbstractStrategy implements StrategyInterface
{
    /**
     * Constructor for AbstractStrategy.
     *
     * @param StrategyFactory $strategyFactory
     */
    public function __construct(private StrategyFactory $strategyFactory) {}

    /**
     * Executes a chain of strategies.
     *
     * @param array $chain
     * @param RequestData|null $requestData
     * @param ResponseData|null $responseData
     * 
     * @return mixed
     */
    protected function chainExecutor(array $chain, ?RequestData $requestData = null, ?ResponseData $responseData = null): mixed
    {
        $value = null;

        // Loop through each step in the chain and execute them while passing the result to the next as value.
        foreach ($chain as $step) {
            $strategyClass = Arr::get($step, 'strategy');
            $options = Arr::get($step, 'options', []);

            $strategy = $this->strategyFactory->make($strategyClass);

            // Pass the current value to the strategy and handle it.
            $value = $strategy->handle($requestData, $responseData, $options, $value);
        }

        return $value;
    }
}