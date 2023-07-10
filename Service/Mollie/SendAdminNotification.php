<?php

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Mollie;

use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order\Email\Container\IdentityInterface;
use Mollie\Subscriptions\Config;

class SendAdminNotification
{
    /**
     * @var \Magento\Framework\Notification\NotifierInterface
     */
    private $notifier;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var TransportBuilder
     */
    private $transportBuilder;
    /**
     * @var IdentityInterface
     */
    private $identityContainer;
    /**
     * @var SenderResolverInterface
     */
    private $senderResolver;
    /**
     * @var UrlInterface
     */
    private $urlInterface;

    public function __construct(
        Config $config,
        TransportBuilder $transportBuilder,
        SenderResolverInterface $senderResolver,
        UrlInterface $urlInterface
    ) {
        $this->config = $config;
        $this->transportBuilder = $transportBuilder;
        $this->senderResolver = $senderResolver;
        $this->urlInterface = $urlInterface;
    }

    public function send(string $id, \Throwable $exception): void
    {
        if (!$this->config->isErrorEmailEnabled()) {
            return;
        }

        $url = $this->urlInterface->getCurrentUrl();
        $sender = $this->senderResolver->resolve($this->config->errorEmailSender());
        $receiver = $this->senderResolver->resolve($this->config->errorEmailReceiver());

        $templateId = $this->config->subscriptionErrorAdminNotificationTemplate();
        $builder = $this->transportBuilder->setTemplateIdentifier($templateId);
        $builder->setTemplateOptions(['area' => 'frontend', 'store' => $this->config->getStore()->getId()]);

        $builder->setFromByScope($sender);
        $builder->setTemplateVars([
            'id' => $id,
            'url' => $url,
            'error' => $exception->__toString(),
        ]);

        $builder->addTo($receiver['email'], $receiver['name']);

        $transport = $builder->getTransport();
        $transport->sendMessage();
    }
}
