<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Shipping\Model\Config\Source\Allmethods;

class ActiveShippingMethods extends AbstractSource
{
    public function __construct(
        private readonly Allmethods $allMethods
    ) {
    }

    public function getAllOptions()
    {
        return $this->allMethods->toOptionArray(true);
    }
}
