<?php

declare(strict_types=1);

namespace DemonDextralHorn\Exceptions;

use InvalidArgumentException;

/**
 * Exception for missing/required strategy option.
 *
 * @class MissingStrategyOptionException
 */
final class MissingStrategyOptionException extends InvalidArgumentException
{
    /**
     * Constructor for MissingStrategyOptionException.
     *
     * @param string $strategy The strategy name.
     * @param string $option The missing option name.
     */
    public function __construct(string $strategy, string $option)
    {
        parent::__construct("{$strategy} requires the \"{$option}\" option.");
    }
}
