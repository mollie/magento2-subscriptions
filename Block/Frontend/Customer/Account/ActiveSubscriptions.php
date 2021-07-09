<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Block\Frontend\Customer\Account;

use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Mollie\Payment\Model\Mollie;
use Mollie\Subscriptions\DTO\SubscriptionResponse;

class ActiveSubscriptions extends Template
{
    /**
     * @var CurrentCustomer
     */
    private $currentCustomer;

    /**
     * @var Mollie
     */
    private $mollie;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var SubscriptionResponse[]|null
     */
    private $subscriptions = null;

    public function __construct(
        Template\Context $context,
        CurrentCustomer $currentCustomer,
        Mollie $mollie,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->currentCustomer = $currentCustomer;
        $this->mollie = $mollie;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @return SubscriptionResponse[]
     */
    public function getSubscriptions()
    {
        if ($this->subscriptions) {
            return $this->subscriptions;
        }

        $customer = $this->currentCustomer->getCustomer();
        $extensionAttributes = $customer->getExtensionAttributes();
        if (!$extensionAttributes || !$extensionAttributes->getMollieCustomerId()) {
            return [];
        }

        $api = $this->mollie->getMollieApi();
        $subscriptions = $api->subscriptions->listForId($extensionAttributes->getMollieCustomerId());

        $this->subscriptions = array_map(function ($subscription) use ($customer) {
            return new SubscriptionResponse($subscription, $customer);
        }, (array)$subscriptions);

        return $this->subscriptions;
    }

    public function hasParent(string $subscriptionId): bool
    {
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->getParentId() == $subscriptionId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param float $amount
     * @return string
     */
    public function formatPrice(float $amount): string
    {
        return $this->priceCurrency->convertAndFormat($amount);
    }
}
