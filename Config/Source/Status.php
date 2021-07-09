<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\DB\Ddl\Table;

class Status extends AbstractSource
{
    const ENABLED = 1;
    const DISABLED = 2;

    public function getAllOptions()
    {
        return [
            [
                'value' => static::ENABLED,
                'label' => __('Enabled'),
            ],
            [
                'value' => static::DISABLED,
                'label' => __('Disabled'),
            ],
        ];
    }

    public function getFlatColumns()
    {
        $columns = [];
        $attributeCode = $this->getAttribute()->getAttributeCode();

        $type = Table::TYPE_INTEGER;
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
