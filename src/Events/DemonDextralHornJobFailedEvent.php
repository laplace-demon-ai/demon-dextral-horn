<?php

declare(strict_types=1);

namespace DemonDextralHorn\Events;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;

/**
 * Event triggered when the DemonDextralHornJob fails.
 *
 * @class DemonDextralHornJobFailedEvent
 */
final readonly class DemonDextralHornJobFailedEvent
{
    /**
     * Create a new DemonDextralHornJobFailedEvent instance.
     *
     * @param RequestData $requestData
     * @param ResponseData $responseData
     */
    public function __construct(
        public RequestData $requestData,
        public ResponseData $responseData
    ) {}
}
