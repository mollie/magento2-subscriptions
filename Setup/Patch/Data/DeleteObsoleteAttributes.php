<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class DeleteObsoleteAttributes implements DataPatchInterface
{
    public function __construct(
        private readonly EavSetupFactory $eavSetupFactory
    ) {
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
