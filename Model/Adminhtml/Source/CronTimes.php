<?php

namespace Mollie\Subscriptions\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CronTimes implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        $output = [];
        for($i = 0; $i < 24; $i++) {
            $output[] = [
                'value' => '0 ' . $i . ' * * *',
                'label' => sprintf('%02d:00', $i),
            ];
        }

        return $output;
    }
}
