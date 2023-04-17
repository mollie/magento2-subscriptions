<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Service\Mollie;

use Magento\Framework\Exception\NotFoundException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Service\Mollie\MollieApiClient;

class RetryUsingOtherStoreViews
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var MollieApiClient
     */
    private $mollieApiClient;

    public function __construct(
        StoreManagerInterface $storeManager,
        StoreRepositoryInterface $storeRepository,
        MollieApiClient $mollieApiClient
    ) {
        $this->storeManager = $storeManager;
        $this->storeRepository = $storeRepository;
        $this->mollieApiClient = $mollieApiClient;
    }

    public function execute(string $id): Payment
    {
        $currentGroup = $this->storeManager->getGroup();
        $currentGroupId = $currentGroup->getId();
        $currentStore = $this->storeManager->getStore();
        $currentStoreId = $currentStore->getId();

        $stores = $this->storeRepository->getList();
        $filtered = array_filter(
            $stores,
            function (StoreInterface $store) use ($currentGroupId, $currentStoreId) {
                return $store->getStoreGroupId() === $currentGroupId &&
                    $store->getId() !== $currentStoreId;
            }
        );

        foreach ($filtered as $store) {
            $api = $this->mollieApiClient->loadByStore($store->getId());

            try {
                return $api->payments->get($id);
            } catch (\Exception $e) {
                // Ignore
            }
        }

        throw new NotFoundException(
            __('Payment with ID %1 not found in any store view', $id)
        );
    }
}
