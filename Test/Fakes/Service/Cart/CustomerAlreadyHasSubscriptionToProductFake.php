<?php

namespace Mollie\Subscriptions\Test\Fakes\Service\Cart;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Customer\Model\Customer;
use Mollie\Subscriptions\Service\Cart\CustomerAlreadyHasSubscriptionToProduct;

class CustomerAlreadyHasSubscriptionToProductFake extends CustomerAlreadyHasSubscriptionToProduct
{
    /**
     * @var bool
     */
    private $result;

    /**
     * @var bool
     */
    private $shouldNotBeCalled = false;

    public function alwaysReturnsTrue(): void
    {
        $this->result = true;
    }

    public function alwaysReturnsFalse(): void
    {
        $this->result = true;
    }

    public function shouldNotBeCalled(): void
    {
        $this->shouldNotBeCalled = true;
    }

    public function execute(Customer $customer, ProductInterface $product): bool
    {
        if ($this->shouldNotBeCalled) {
            throw new \Exception('This method should not be called');
        }

        if ($this->result === null) {
            throw new \Exception('Please call "alwaysReturnsFalse" or "alwaysReturnsTrue" first.');
        }

        return $this->result;
    }
}
