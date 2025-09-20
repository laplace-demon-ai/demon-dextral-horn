<?php

declare(strict_types=1);

namespace DemonDextralHorn\Factories;

use DemonDextralHorn\Resolvers\Strategies\StrategyInterface;
use Exception;
use InvalidArgumentException;
use RuntimeException;

/**
 * Factory class for creating strategy instance from string class name.
 *
 * @class StrategyFactory
 */
final class StrategyFactory
{
    /**
     * Create an instance of a strategy class given its class name (the fully qualified class name of the strategy).
     *
     * @param string $strategyClass
     *
     * @return StrategyInterface
     */
    public function make(string $strategyClass): StrategyInterface
    {
        // Check if strategy class is provided
        if (empty($strategyClass)) {
            throw new InvalidArgumentException('Strategy class not provided');
        }

        // Check if class exists
        if (! class_exists($strategyClass)) {
            throw new InvalidArgumentException("Strategy class does not exist: {$strategyClass}");
        }

        // Check if class implements expected interface
        if (! is_subclass_of($strategyClass, StrategyInterface::class)) {
            throw new InvalidArgumentException("Class {$strategyClass} must implement StrategyInterface");
        }

        try {
            return app()->make($strategyClass);
        } catch (Exception $e) {
            throw new RuntimeException("Failed to instantiate strategy {$strategyClass}");
        }
    }
}
