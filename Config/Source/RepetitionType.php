<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\DB\Ddl\Table;

class RepetitionType extends AbstractSource
{
    const TIMES = 'times';
    const INFINITE = 'infinite';

    public function getAllOptions()
    {
        return [
            [
                'value' => '',
                'label' => __('Please select'),
            ],
            [
                'value' => static::TIMES,
                'label' => __('Times'),
            ],
            [
                'value' => static::INFINITE,
                'label' => __('Infinite'),
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
