<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions\Model\Log;

use Mollie\Subscriptions\Api\Log\RepositoryInterface as LogRepositoryInterface;
use Mollie\Subscriptions\Logger\DebugLogger;
use Mollie\Subscriptions\Logger\ErrorLogger;

/**
 * Logs repository class
 */
class Repository implements LogRepositoryInterface
{
    public function __construct(
        private readonly DebugLogger $debugLogger,
        private readonly ErrorLogger $errorLogger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function addErrorLog(string $type, $data)
    {
        $this->errorLogger->addLog($type, $data);
    }

    /**
     * @inheritDoc
     */
    public function addDebugLog(string $type, $data)
    {
        $this->debugLogger->addLog($type, $data);
    }
}
