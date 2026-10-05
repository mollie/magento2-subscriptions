<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Test\Integration\Cron;

use Magento\Cron\Model\ConfigInterface;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Subscriptions\Config;

class CronConfigTest extends IntegrationTestCase
{
    public function testThePrepaymentReminderJobReadsItsScheduleFromTheAdminField(): void
    {
        $jobs = $this->objectManager->get(ConfigInterface::class)->getJobs();

        $this->assertArrayHasKey('mollie_subscriptions_send_prepayment_reminders', $jobs['default']);
        $this->assertSame(
            Config::XML_PATH_PREPAYMENT_REMINDER_CRON_EXPR,
            $jobs['default']['mollie_subscriptions_send_prepayment_reminders']['config_path'],
        );
    }

    public function testThereIsNoJobWithoutACallback(): void
    {
        $jobs = $this->objectManager->get(ConfigInterface::class)->getJobs();

        $this->assertArrayNotHasKey('mollie_subscriptions_cron', $jobs['default']);
    }
}
