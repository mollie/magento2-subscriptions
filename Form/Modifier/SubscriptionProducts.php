<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Boolean;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Field;
use Mollie\Subscriptions\Config;
use Mollie\Subscriptions\Config\Source\IntervalType;
use Mollie\Subscriptions\Config\Source\RepetitionType;

class SubscriptionProducts extends AbstractModifier
{
    public const ALLOW_ONE_TIME_PURCHASE = 'mollie_allow_one_time_purchase';

    /**
     * @var Config
     */
    private $config;

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

    /**
     * @var LocatorInterface
     */
    private $locator;

    /**
     * @var string[]
     */
    private $supportedProductTypeIds;

    public function __construct(
        Config $config,
        ArrayManager $arrayManager,
        IntervalType $intervalType,
        RepetitionType $repetitionType,
        LocatorInterface $locator,
        array $supportedProductTypeIds
    ) {
        $this->config = $config;
        $this->arrayManager = $arrayManager;
        $this->intervalType = $intervalType;
        $this->repetitionType = $repetitionType;
        $this->locator = $locator;
        $this->supportedProductTypeIds = $supportedProductTypeIds;
    }

    public function modifyMeta(array $meta): array
    {
        if (!in_array($this->locator->getProduct()->getTypeId(), $this->supportedProductTypeIds)) {
            unset($meta['mollie']);
            return $meta;
        }

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

        $meta = $this->customizeOneTimePurchaseField($meta);

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
     * Customization of allow gift message field
     *
     * @param array $meta
     * @return array
     */
    private function customizeOneTimePurchaseField(array $meta)
    {
        $groupCode = $this->getGroupCodeByField($meta, 'container_' . static::ALLOW_ONE_TIME_PURCHASE);

        if (!$groupCode) {
            return $meta;
        }

        $containerPath = $this->arrayManager->findPath(
            'container_' . static::ALLOW_ONE_TIME_PURCHASE,
            $meta,
            null,
            'children'
        );
        $fieldPath = $this->arrayManager->findPath(static::ALLOW_ONE_TIME_PURCHASE, $meta, null, 'children');
        $fieldConfig = $this->arrayManager->get($fieldPath, $meta);

        $meta = $this->arrayManager->merge(
            $containerPath,
            $meta,
            [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'formElement' => 'container',
                            'componentType' => 'container',
                            'component' => 'Magento_Ui/js/form/components/group',
                            'label' => false,
                            'required' => false,
                            'breakLine' => false,
                            'sortOrder' => $fieldConfig['arguments']['data']['config']['sortOrder'],
                            'dataScope' => '',
                        ],
                    ],
                ],
            ]
        );
        $meta = $this->arrayManager->merge(
            $containerPath,
            $meta,
            [
                'children' => [
                    static::ALLOW_ONE_TIME_PURCHASE => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'dataScope' => static::ALLOW_ONE_TIME_PURCHASE,
                                    'additionalClasses' => 'admin__field-x-small',
                                    'component' => 'Magento_Ui/js/form/element/single-checkbox-use-config',
                                    'componentType' => Field::NAME,
                                    'prefer' => 'toggle',
                                    'valueMap' => [
                                        'false' => '0',
                                        'true' => '1',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'use_config_' . static::ALLOW_ONE_TIME_PURCHASE => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'dataType' => 'number',
                                    'formElement' => Checkbox::NAME,
                                    'componentType' => Field::NAME,
                                    'description' => __('Use Config Settings'),
                                    'dataScope' => 'use_config_' . static::ALLOW_ONE_TIME_PURCHASE,
                                    'valueMap' => [
                                        'false' => '0',
                                        'true' => '1',
                                    ],
                                    'exports' => [
                                        'checked' => '${$.parentName}.' . static::ALLOW_ONE_TIME_PURCHASE
                                            . ':isUseConfig',
                                        '__disableTmpl' => ['checked' => false],
                                    ],
                                    'imports' => [
                                        'disabled' => '${$.parentName}.' . static::ALLOW_ONE_TIME_PURCHASE
                                            . ':isUseDefault',
                                        '__disableTmpl' => ['disabled' => false],
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data): array
    {
        $modelId = $this->locator->getProduct()->getId();
        $useConfigValue = Boolean::VALUE_USE_CONFIG;

        $isConfigured = isset($data[$modelId][static::DATA_SOURCE_DEFAULT][static::ALLOW_ONE_TIME_PURCHASE]);

        $isConfigUsed = isset($data[$modelId][static::DATA_SOURCE_DEFAULT][static::ALLOW_ONE_TIME_PURCHASE])
            && $data[$modelId][static::DATA_SOURCE_DEFAULT][static::ALLOW_ONE_TIME_PURCHASE] == $useConfigValue;

        if (!$isConfigured || $isConfigUsed || empty($modelId)) {
            $data[$modelId][static::DATA_SOURCE_DEFAULT][static::ALLOW_ONE_TIME_PURCHASE] =
                $this->config->allowOneTimePurchase();
            $data[$modelId][static::DATA_SOURCE_DEFAULT]['use_config_' . static::ALLOW_ONE_TIME_PURCHASE] = '1';
        }

        return $data;
    }
}
