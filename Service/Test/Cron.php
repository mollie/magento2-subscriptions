<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Test;

use Magento\Cron\Model\ResourceModel\Schedule\Collection as ScheduleCollection;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Cron\Model\Schedule;

/**
 * Cron test class
 */
class Cron
{

    /**
     * Test type
     */
    const TYPE = 'cron_test';

    /**
     * Test description
     */
    const TEST = 'Check if cron is enabled and running';

    /**
     * Visibility
     */
    const VISIBLE = true;

    /**
     * Message on test success
     */
    const SUCCESS_MSG = 'Cron last ran at %s';

    /**
     * Message on test failed
     */
    const FAILED_MSG = 'No active Magento cron found in the last hour!';

    /**
     * Expected result
     */
    const EXPECTED = true;

    /**
     * Cron delay value
     */
    const CRON_DELAY = 3600;

    /**
     * Link to get support
     */
    const SUPPORT_LINK = 'https://www.magmodules.eu/help/magento2/cronjob-setup.html';

    /**
     * @var CollectionFactory
     */
    private $scheduleCollectionFactory;

    public function __construct(
        CollectionFactory $scheduleCollectionFactory
    ) {
        $this->scheduleCollectionFactory = $scheduleCollectionFactory;
    }

    /**
     * @return array
     */
    public function execute()
    {
        $scheduledAt = '';
        $result = [
            'type' => self::TYPE,
            'test' => self::TEST,
            'visible' => self::VISIBLE,

        ];

        /** @var ScheduleCollection $collection */
        $collection = $this->scheduleCollectionFactory->create();
        $scheduleCollection = $collection
            ->addFieldToSelect('scheduled_at')
            ->addFieldToFilter('status', 'success');

        $scheduleCollection->getSelect()
            ->limit(1)
            ->order('scheduled_at DESC');
        if ($scheduleCollection->getSize() == 0) {
            $cronStatus = false;
        } else {
            $scheduledAt = $scheduleCollection->getFirstItem()->getScheduledAt();
            $cronStatus = (time() - strtotime($scheduledAt)) < self::CRON_DELAY;
        }
        if ($cronStatus == self::EXPECTED) {
            $result['result_msg'] = sprintf(self::SUCCESS_MSG, $scheduledAt);
            $result +=
                [
                    'result_code' => 'success'
                ];
        } else {
            $result['result_msg'] = self::FAILED_MSG;
            $result +=
                [
                    'result_code' => 'failed',
                    'support_link' => self::SUPPORT_LINK
                ];
        }
        return $result;
    }
}
