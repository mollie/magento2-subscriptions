<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Mollie;

use Magento\Framework\DataObject;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\Address\RateCollectorInterfaceFactory;
use Magento\Quote\Model\Quote\Address\RateRequestFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Shipping\Model\Rate\CarrierResult;
use Mollie\Subscriptions\Config;

class GetShippingCostForOrderItem
{
    /**
     * @var RateRequestFactory
     */
    private $rateRequestFactory;

    /**
     * @var RateCollectorInterfaceFactory
     */
    private $rateCollectorFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var OrderInterface
     */
    private $order;

    public function __construct(
        RateRequestFactory $rateRequestFactory,
        RateCollectorInterfaceFactory $rateCollectorFactory,
        CartRepositoryInterface $cartRepository,
        Config $config
    ) {
        $this->rateRequestFactory = $rateRequestFactory;
        $this->rateCollectorFactory = $rateCollectorFactory;
        $this->cartRepository = $cartRepository;
        $this->config = $config;
    }

    public function execute(OrderInterface $order, OrderItemInterface $orderItem): float
    {
        if ($order->getIsVirtual()) {
            return 0.0;
        }

        $this->order = $order;

        $result = $this->getCarrierResult($orderItem);

        if ($price = $this->getRateByCarrier($result)) {
            return $price;
        }

        $rates = $result->getAllRates();
        if (!$rates) {
            return 0.0;
        }

        /** @var Method $rate */
        $rate = array_shift($rates);
        return $rate->getPrice();
    }

    private function getRateByCarrier(CarrierResult $result): ?float
    {
        $shippingMethod = $this->config->getShippingMethod();
        if (!$shippingMethod) {
            return null;
        }

        [$method] = explode('_', $shippingMethod);
        $rates = $result->getRatesByCarrier($method);
        if (!$rates) {
            return null;
        }

        /** @var Method $rate */
        $rate = array_shift($rates);
        return $rate->getData('price');
    }

    private function getCarrierResult(OrderItemInterface $orderItem): CarrierResult
    {
        $request = $this->rateRequestFactory->create();

        // Some shipping methods need this (Yes, I'm looking at you, TableRates and Amasty)
        $address = $this->order->getShippingAddress();

        if ($this->order->getQuoteId()) {
            $quote = $this->cartRepository->get($this->order->getQuoteId());
            $address->setQuote($quote);
        }

        $orderItem->setAddress($address);

        $request->setAllItems([$orderItem]);
        $request->setDestCountryId($address->getCountryId());
        $request->setDestRegionId($address->getRegionId());
        $request->setDestPostcode($address->getPostcode());

        $product = $orderItem->getProduct();
        $request->setPackageValue($product->getFinalPrice());
        $request->setPackageValueWithDiscount($product->getFinalPrice());
        $request->setPackageWeight($product->getWeight());
        $request->setPackageQty(1);

        $rateCollector = $this->rateCollectorFactory->create();
        return $rateCollector->collectRates($request)->getResult();
    }
}
