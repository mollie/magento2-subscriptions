<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class DeleteObsoleteAttributes implements DataPatchInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    public function __construct(
        EavSetupFactory $eavSetupFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function apply(): self
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create();

        $eavSetup->removeAttribute(Product::ENTITY, 'mollie_subscription_interval_amount');
        $eavSetup->removeAttribute(Product::ENTITY, 'mollie_subscription_interval_type');
        $eavSetup->removeAttribute(Product::ENTITY, 'mollie_subscription_repetition_amount');
        $eavSetup->removeAttribute(Product::ENTITY, 'mollie_subscription_repetition_type');

        return $this;
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [ConvertToSubscriptionsTable::class];
    }
}
