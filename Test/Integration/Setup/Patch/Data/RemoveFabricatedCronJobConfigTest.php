<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Test\Integration\Setup\Patch\Data;

use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Subscriptions\Setup\Patch\Data\RemoveFabricatedCronJobConfig;

class RemoveFabricatedCronJobConfigTest extends IntegrationTestCase
{
    /**
     * @magentoDbIsolation enabled
     */
    public function testRemovesTheObsoleteCronScheduleConfig(): void
    {
        $this->objectManager->get(WriterInterface::class)->save(
            RemoveFabricatedCronJobConfig::OBSOLETE_CRON_SCHEDULE_PATH,
            '0 1 * * *',
        );
        $this->reinitConfig();
        $this->assertSame('0 1 * * *', $this->getObsoleteConfigValue());

        $this->objectManager->create(RemoveFabricatedCronJobConfig::class)->apply();
        $this->reinitConfig();

        $this->assertNull($this->getObsoleteConfigValue());
    }

    private function getObsoleteConfigValue(): ?string
    {
        return $this->objectManager->get(ReinitableConfigInterface::class)
            ->getValue(RemoveFabricatedCronJobConfig::OBSOLETE_CRON_SCHEDULE_PATH);
    }

    private function reinitConfig(): void
    {
        $this->objectManager->get(ReinitableConfigInterface::class)->reinit();
    }
}
