<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\DB\Ddl\Table;

class IntervalType extends AbstractSource
{
    const DAYS = 'days';
    const WEEKS = 'weeks';
    const MONTHS = 'months';

    public function getAllOptions()
    {
        return [
            [
                'value' => '',
                'label' => __('Please select'),
            ],
            [
                'value' => static::DAYS,
                'label' => __('Day(s)'),
            ],
            [
                'value' => static::WEEKS,
                'label' => __('Week(s)'),
            ],
            [
                'value' => static::MONTHS,
                'label' => __('Month(s)'),
            ],
        ];
    }

    public function getFlatColumns()
    {
        $columns = [];
        $attributeCode = $this->getAttribute()->getAttributeCode();

        $type = Table::TYPE_TEXT;
        $columns[$attributeCode] = [
            'type' => $type,
            'unsigned' => false,
            'nullable' => true,
            'default' => null,
            'extra' => null,
            'comment' => $attributeCode . ' column',
        ];

        return $columns;
    }
}
