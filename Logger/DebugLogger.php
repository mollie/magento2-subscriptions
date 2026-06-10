<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions\Logger;

use Magento\Framework\Serialize\Serializer\Json;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

class DebugLogger
{
    /**
     * @var Logger
     */
    private $instance;

    public function __construct(
        private readonly Json $json,
        private readonly Logger $logger,
        private readonly StreamHandler $handler
    ) {
    }

    private function getLogger(): Logger
    {
        if ($this->instance) {
            return $this->instance;
        }

        $this->instance = $this->logger;
        $this->instance->pushHandler($this->handler);

        return $this->instance;
    }

    /**
     * Add debug data Log
     *
     * @param string $type
     * @param mixed $data
     *
     */
    public function addLog(string $type, $data): void
    {
        $level = class_exists(Level::class) ? Level::Debug : 100;
        $data = is_array($data) || is_object($data) ? $this->json->serialize($data) : $data;
        $this->getLogger()->addRecord($level, $type . ': ' . $data);
    }
}
