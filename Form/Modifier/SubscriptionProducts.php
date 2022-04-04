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
use Mollie\Subscriptions\Config\Source\IntervalType;
use Mollie\Subscriptions\Config\Source\RepetitionType;

class SubscriptionProducts extends AbstractModifier
{
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var IntervalType
     */
    private $intervalType;

    /**
     * @var RepetitionType
     */
    private $repetitionType;

    public function __construct(
        ArrayManager $arrayManager,
        IntervalType $intervalType,
        RepetitionType $repetitionType
    ) {
        $this->arrayManager = $arrayManager;
        $this->intervalType = $intervalType;
        $this->repetitionType = $repetitionType;
    }

    public function modifyMeta(array $meta): array
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

        $subscriptionTable = $this->arrayManager->findPath('mollie_subscription_table', $meta, null, 'children');

        return $this->arrayManager->merge(
            $subscriptionTable . self::META_CONFIG_PATH,
            $meta,
            [
                'component' => 'Mollie_Subscriptions/js/product/input/subscription-table',
                'interval_types' => $this->intervalType->getAllOptions(),
                'repetition_types' => $this->repetitionType->getAllOptions(),
            ]
        );
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
    public function modifyData(array $data): array
    {
        return $data;
    }
}
