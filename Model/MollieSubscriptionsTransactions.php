<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Resources\PaymentCollection;
use Mollie\Payment\Api\MollieCustomerRepositoryInterface;
use Mollie\Subscriptions\Config;
use Mollie\Subscriptions\Service\Mollie\MollieSubscriptionApi;

class MollieSubscriptionsTransactions extends Listing
{
    private ?string $next = null;

    private ?string $previous = null;

    public function __construct(
        ContextInterface $context,
        private readonly MollieSubscriptionApi $mollieSubscriptionApi,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
    }

    public function getDataSourceData(): array
    {
        $storeId = storeId($this->getContext()->getRequestParam('filters')['store_id'] ?? null);
        $customerId = $this->getContext()->getRequestParam('customer_id');
        $subscriptionId = $this->getContext()->getRequestParam('subscription_id');

        $api = $this->mollieSubscriptionApi->loadByStore($storeId);

        $paging = $this->getContext()->getRequestParam('paging');

        $pageSize = (int) ($paging['pageSize'] ?? 20);
        if ($pageSize > 250) {
            $pageSize = 250;
        }

        $result = $api->subscriptionPayments->pageForIds(
            $customerId,
            $subscriptionId,
            $this->getContext()->getRequestParam('offsetID'),
            $pageSize,
        );

        $this->parsePreviousNext($result);

        $items = array_map(function (Payment $payment) {
            return [
                'id' => $payment->id,
                'amount' => $payment->amount->value,
                'description' => $payment->description,
                'status' => $payment->status,
                'created_at' => $payment->createdAt,
                'paid_at' => $payment->paidAt,
                'canceled_at' => $payment->canceledAt,
                'expires_at' => $payment->expiresAt,
                'failed_at' => $payment->failedAt,
                'due_date' => $payment->dueDate,
            ];
        }, (array)$result);

        return [
            'data' => [
                'items' => $items,
                'nextID' => $this->next,
                'previousID' => $this->previous,
            ],
        ];
    }

    private function parseLink(string $link): string
    {
        $query = parse_url($link, PHP_URL_QUERY);
        parse_str($query, $parts);

        return $parts['from'];
    }

    private function parsePreviousNext(PaymentCollection $result): void
    {
        if ($result->hasNext()) {
            $this->next = $this->parseLink($result->_links->next->href);
        }

        if ($result->hasPrevious()) {
            $this->previous = $this->parseLink($result->_links->previous->href);
        }
    }
}
