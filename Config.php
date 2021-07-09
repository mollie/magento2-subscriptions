<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Config repository class
 */
class Config
{
    const EXTENSION_CODE = 'Mollie_Subscriptions';
    const XML_PATH_EXTENSION_VERSION = 'mollie_subscriptions/general/version';
    const XML_PATH_EXTENSION_ENABLE = 'mollie_subscriptions/general/enable';
    const XML_PATH_EXTENSION_SHIPPING_METHOD = 'mollie_subscriptions/general/shipping_method';
    const XML_PATH_DEBUG = 'mollie_subscriptions/general/debug';
    const XML_PATH_ADD_TO_CART_TEXT = 'mollie_subscriptions/general/add_to_cart_text';
    const MODULE_SUPPORT_LINK = 'https://www.magmodules.eu/help/%s';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ProductMetadataInterface
     */
    private $metadata;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ProductMetadataInterface $metadata,
        EncryptorInterface $encryptor
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->metadata = $metadata;
        $this->encryptor = $encryptor;
    }

    /**
     * Get Configuration data
     *
     * @param string $path
     * @param int|null $storeId
     * @param string|null $scope
     *
     * @return string
     */
    private function getStoreValue(
        string $path,
        $storeId = null,
        string $scope = null
    ): string {
        if (!$storeId) {
            $storeId = (int)$this->getStore()->getId();
        }
        $scope = $scope ?? ScopeInterface::SCOPE_STORE;
        return (string)$this->scopeConfig->getValue($path, $scope, (int)$storeId);
    }

    /**
     * @return string
     */
    public function getExtensionVersion(): string
    {
        return $this->getStoreValue(self::XML_PATH_EXTENSION_VERSION);
    }

    /**
     * @return StoreInterface
     */
    public function getStore(): StoreInterface
    {
        try {
            return $this->storeManager->getStore();
        } catch (Exception $e) {
            if ($store = $this->storeManager->getDefaultStoreView()) {
                return $store;
            }
        }
        $stores = $this->storeManager->getStores();
        return reset($stores);
    }

    /**
     * @return string
     */
    public function getMagentoVersion(): string
    {
        return $this->metadata->getVersion();
    }

    /**
     * Get config value flag
     *
     * @param string $path
     * @param int|null $storeId
     * @param string|null $scope
     *
     * @return bool
     */
    private function getFlag(string $path, int $storeId = null, string $scope = null): bool
    {
        if (!$storeId) {
            $storeId = (int)$this->getStore()->getId();
        }
        $scope = $scope ?? ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->isSetFlag($path, $scope, (int)$storeId);
    }

    /**
     * @return string
     */
    public function getExtensionCode(): string
    {
        return self::EXTENSION_CODE;
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isDebugMode(int $storeId = null): bool
    {
        $scope = $scope ?? ScopeInterface::SCOPE_STORE;
        return $this->getFlag(
            self::XML_PATH_DEBUG,
            $storeId,
            $scope
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(int $storeId = null): bool
    {
        return $this->getFlag(self::XML_PATH_EXTENSION_ENABLE, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getShippingMethod(int $storeId = null): string
    {
        return $this->getStoreValue(self::XML_PATH_EXTENSION_SHIPPING_METHOD, $storeId);
    }

    /**
     * Support link for extension.
     *
     * @return string
     */
    public function getSupportLink(): string
    {
        return sprintf(
            self::MODULE_SUPPORT_LINK,
            $this->getExtensionCode()
        );
    }

    /**
     * @param null|int|string $storeId
     * @param string $scope
     * @return string|null
     */
    public function getAddToCartText($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getStoreValue(static::XML_PATH_ADD_TO_CART_TEXT, $storeId, $scope);
    }
}
