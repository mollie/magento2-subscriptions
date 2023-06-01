<?php

namespace Mollie\Subscriptions\Service\Mollie;

use Mollie\Payment\Service\Mollie\MollieApiClient;

class MollieSubscriptionApi extends MollieApiClient
{
    public function loadByApiKey(string $apiKey): \Mollie\Api\MollieApiClient
    {
        $client = parent::loadByApiKey($apiKey);
        $client->addVersionString('MagentoSubscription');

        return $client;
    }

    public function loadByStore(int $storeId = null): \Mollie\Api\MollieApiClient
    {
        $client = parent::loadByStore($storeId);
        $client->addVersionString('MagentoSubscription');

        return $client;
    }
}
