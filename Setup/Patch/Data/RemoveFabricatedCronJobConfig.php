<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * The prepayment reminder schedule used to be stored under crontab/default/jobs/mollie_subscriptions_cron. Magento
 * treats every crontab/default/jobs/<name> config entry as a cron job, so this created a job without a callback
 * that failed every night with "No callbacks found for cron job mollie_subscriptions_cron".
 */
class RemoveFabricatedCronJobConfig implements DataPatchInterface
{
    public const OBSOLETE_CRON_SCHEDULE_PATH = 'crontab/default/jobs/mollie_subscriptions_cron/schedule/cron_expr';

    public function __construct(
        private readonly WriterInterface $configWriter
    ) {
    }

    public function apply(): self
    {
        $this->configWriter->delete(self::OBSOLETE_CRON_SCHEDULE_PATH);

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
