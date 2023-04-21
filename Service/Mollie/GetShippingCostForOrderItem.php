<?php

namespace Mollie\Subscriptions\Service\Mollie;

use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Address\RateCollectorInterfaceFactory;
use Magento\Quote\Model\Quote\Address\RateRequestFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
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

    public function __construct(
        RateRequestFactory $rateRequestFactory,
        RateCollectorInterfaceFactory $rateCollectorFactory,
        Config $config
    ) {
        $this->rateRequestFactory = $rateRequestFactory;
        $this->rateCollectorFactory = $rateCollectorFactory;
        $this->config = $config;
    }

    public function execute(OrderItemInterface $orderItem): float
    {
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

        // Some shipping methods need this (Yes, I'm looking at you, TableRates)
        $orderItem->setAddress(new DataObject());

        $request->setAllItems([$orderItem]);
        $request->setDestCountryId('US');
        $request->setDestRegionId(0);
        $request->setDestPostcode('90210');

        $product = $orderItem->getProduct();
        $request->setPackageValue($product->getFinalPrice());
        $request->setPackageValueWithDiscount($product->getFinalPrice());
        $request->setPackageWeight($product->getWeight());
        $request->setPackageQty(1);

        $rateCollector = $this->rateCollectorFactory->create();
        return $rateCollector->collectRates($request)->getResult();
    }
}
