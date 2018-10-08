<?php
/**
 * Copyright © Swarming Technology, LLC. All rights reserved.
 */
namespace Buzzi\PublishNewsletterSubscribe\Plugin\Newsletter;

use Magento\Newsletter\Model\Subscriber as NewsletterSubscriber;
use Magento\Store\Model\ScopeInterface;
use Buzzi\PublishNewsletterSubscribe\Model\DataBuilder;

class Subscriber
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Buzzi\Publish\Model\Config\Events
     */
    private $configEvents;

    /**
     * @var \Buzzi\Publish\Api\QueueInterface
     */
    private $queue;

    /**
     * @var \Buzzi\PublishNewsletterSubscribe\Model\DataBuilder
     */
    private $dataBuilder;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Buzzi\Publish\Model\Config\Events $configEvents
     * @param \Buzzi\Publish\Api\QueueInterface $queue
     * @param \Buzzi\PublishNewsletterSubscribe\Model\DataBuilder $dataBuilder
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Buzzi\Publish\Model\Config\Events $configEvents,
        \Buzzi\Publish\Api\QueueInterface $queue,
        \Buzzi\PublishNewsletterSubscribe\Model\DataBuilder $dataBuilder
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configEvents = $configEvents;
        $this->queue = $queue;
        $this->dataBuilder = $dataBuilder;
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subject
     * @param string $result
     * @return string
     */
    public function afterSubscribe(NewsletterSubscriber $subject, $result)
    {
        $storeId = $subject->getStoreId();

        if ($this->configEvents->isEventEnabled(DataBuilder::EVENT_TYPE_SUBSCRIBE, $storeId)
            && $this->isSubscribeEventAllowed($result, $storeId)
        ) {
            $this->sendSubscribeEvent($subject, $storeId);
        }

        return $result;
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subject
     * @param bool $result
     * @return bool
     */
    public function afterConfirm(NewsletterSubscriber $subject, $result)
    {
        $storeId = $subject->getStoreId();

        if (true === $result && $this->configEvents->isEventEnabled(DataBuilder::EVENT_TYPE_SUBSCRIBE, $storeId)) {
            $this->sendSubscribeEvent($subject, $storeId);
        }

        return $result;
    }

    /**
     * @param string $status
     * @param int $storeId
     * @return bool
     */
    private function isSubscribeEventAllowed($status, int $storeId)
    {
        return !$this->scopeConfig->getValue(NewsletterSubscriber::XML_PATH_CONFIRMATION_FLAG, ScopeInterface::SCOPE_STORE)
            || !$this->configEvents->getValue(DataBuilder::EVENT_TYPE_SUBSCRIBE, 'confirmed_only', $storeId)
            || $status === NewsletterSubscriber::STATUS_SUBSCRIBED;
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param int $storeId
     * @return void
     */
    private function sendSubscribeEvent(NewsletterSubscriber $subscriber, $storeId)
    {
        $payload = $this->dataBuilder->getPayload($subscriber, DataBuilder::EVENT_TYPE_SUBSCRIBE);

        if ($this->configEvents->isCron(DataBuilder::EVENT_TYPE_SUBSCRIBE, $storeId)) {
            $this->queue->add(DataBuilder::EVENT_TYPE_SUBSCRIBE, $payload, $storeId);
        } else {
            $this->queue->send(DataBuilder::EVENT_TYPE_SUBSCRIBE, $payload, $storeId);
        }
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subject
     * @param \Magento\Newsletter\Model\Subscriber $result
     * @return \Magento\Newsletter\Model\Subscriber
     */
    public function afterUnsubscribe(NewsletterSubscriber $subject, $result)
    {
        $storeId = $subject->getStoreId();

        if ($this->configEvents->isEventEnabled(DataBuilder::EVENT_TYPE_UNSUBSCRIBE, $storeId)) {
            $this->sendUnsubscribeEvent($subject, $storeId);
        }
        return $result;
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param int $storeId
     * @return void
     */
    private function sendUnsubscribeEvent(NewsletterSubscriber $subscriber, $storeId)
    {
        $payload = $this->dataBuilder->getPayload($subscriber, DataBuilder::EVENT_TYPE_UNSUBSCRIBE);

        if ($this->configEvents->isCron(DataBuilder::EVENT_TYPE_UNSUBSCRIBE, $storeId)) {
            $this->queue->add(DataBuilder::EVENT_TYPE_UNSUBSCRIBE, $payload, $storeId);
        } else {
            $this->queue->send(DataBuilder::EVENT_TYPE_UNSUBSCRIBE, $payload, $storeId);
        }
    }
}
