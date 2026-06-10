<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Mollie\Api\Resources\Subscription;
use Mollie\Api\Resources\SubscriptionCollection;
use Mollie\Payment\Service\Mollie\MollieApiClient;

class UpdateWebhookPath implements DataPatchInterface
{
    private array $storeCodeToId = [];

    public function __construct(
        private readonly StoreRepositoryInterface $storeRepository,
        private readonly UrlInterface $urlBuilder,
        private readonly MollieApiClient $mollieApiClient
    ) {
    }

    public function apply(): self
    {
        foreach ($this->storeRepository->getList() as $store) {
            $this->storeCodeToId[$store->getCode()] = $store->getId();
        }

        foreach ($this->storeRepository->getList() as $store) {
            try {
                $api = $this->mollieApiClient->loadByStore(storeId($store->getId()));
            } catch (\Exception $exception) {
                continue;
            }

            $subscriptions = $api->subscriptions->allFor();
            $this->updateSubscriptions($subscriptions);
        }

        return $this;
    }

    private function updateSubscriptions(SubscriptionCollection $subscriptions): void
    {
        /** @var Subscription $subscription */
        foreach ($subscriptions as $subscription) {
            if (!$subscription->isActive()) {
                return;
            }

            $this->updateSubscription($subscription);
        }

        if ($subscriptions->hasNext()) {
            $this->updateSubscriptions($subscriptions->next());
        }
    }

    public function updateSubscription(Subscription $subscription): void
    {
        /** @var string $webhookUrl */
        $webhookUrl = $subscription->webhookUrl;
        if (strpos($webhookUrl, 'mollie-subscriptions/api/webhook') === false) {
            return;
        }

        // Get either /___store/<match>/ or ___store=<match> from the url
        preg_match('/\/___store\/([^\/]+)\/|___store=([^&]+)/', $webhookUrl, $matches);
        $storeCode = $matches[1] ?? $matches[2];

        if (!array_key_exists($storeCode, $this->storeCodeToId)) {
            return;
        }

        $id = $this->storeCodeToId[$storeCode];

        $newUrl = $this->urlBuilder->getUrl(
            'mollie-subscriptions/api/webhook',
            ['___store' => $id]
        );

        $subscription->webhookUrl = $newUrl;
        $subscription->update();
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [];
    }
}
