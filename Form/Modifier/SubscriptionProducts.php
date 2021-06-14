<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Model\Mollie;

class SubscriptionProducts extends AbstractModifier
{
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    public function __construct(
        ArrayManager $arrayManager
    ) {
        $this->arrayManager = $arrayManager;
    }

    public function modifyMeta(array $meta)
    {
        $repetitionAmountField = 'mollie_subscription_repetition_amount';
        $repetitionTypeField = 'mollie_subscription_repetition_type';

        $meta = $this->mergeToGroup(
            $meta,
            'mollie_subscription_interval_amount',
            'mollie_subscription_interval_type'
        );

        $meta = $this->mergeToGroup(
            $meta,
            $repetitionAmountField,
            $repetitionTypeField
        );

        return $meta;
    }

    public function mergeToGroup(array $meta, $field1, $field2): array
    {
        $field1Path = $this->arrayManager->findPath($field1, $meta, null, 'children');
        $field2Path = $this->arrayManager->findPath($field2, $meta, null, 'children');

        if ($field1Path && $field2Path) {
            $field1ContainerPath = $this->arrayManager->slicePath($field1Path, 0, -2);
            $field2ContainerPath = $this->arrayManager->slicePath($field2Path, 0, -2);

            $meta = $this->arrayManager->merge(
                $field1ContainerPath . self::META_CONFIG_PATH,
                $meta,
                [
                    'breakLine' => false,
                    'component' => 'Magento_Ui/js/form/components/group',
                ]
            );

            $meta = $this->arrayManager->set(
                $field1ContainerPath . '/children/' . $field2,
                $meta,
                $this->arrayManager->get($field2Path, $meta)
            );

            $meta = $this->arrayManager->remove($field2ContainerPath, $meta);
        }

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }
}
