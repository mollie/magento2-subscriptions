<?php

declare(strict_types=1);

namespace Mollie\Subscriptions\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Mollie\Subscriptions\Model\Adminhtml\Backend\SaveCronValue;

class SetDefaultCronSchedule implements DataPatchInterface
{
    public function __construct(
        private readonly WriterInterface $configWriter
    ) {
    }

    public function apply(): self
    {
        $this->configWriter->save(SaveCronValue::CRON_SCHEDULE_PATH, '0 1 * * *');

        return $this;
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [];
    }
}
