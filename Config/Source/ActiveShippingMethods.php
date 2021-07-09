<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Shipping\Model\Config\Source\Allmethods;

class ActiveShippingMethods extends AbstractSource
{
    /**
     * @var Allmethods
     */
    private $allMethods;

    public function __construct(
        Allmethods $allMethods
    ) {
        $this->allMethods = $allMethods;
    }

    public function getAllOptions()
    {
        return $this->allMethods->toOptionArray(true);
    }
}
