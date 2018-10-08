<?php
/**
 * Copyright © Swarming Technology, LLC. All rights reserved.
 */
namespace Buzzi\PublishNewsletterSubscribe\Model;

use Magento\Framework\DataObject;
use Magento\Newsletter\Model\Subscriber;

class DataBuilder
{
    const EVENT_TYPE_SUBSCRIBE = 'buzzi.ecommerce.newsletter-subscribe';
    const EVENT_TYPE_UNSUBSCRIBE = 'buzzi.ecommerce.newsletter-unsubscribe';

    /**
     * @var \Buzzi\Publish\Helper\DataBuilder\Base
     */
    private $dataBuilderBase;

    /**
     * @var \Buzzi\Publish\Helper\DataBuilder\Customer
     */
    private $dataBuilderCustomer;

    /**
     * @var \Magento\Customer\Model\CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventDispatcher;

    /**
     * @param \Buzzi\Publish\Helper\DataBuilder\Base $dataBuilderBase
     * @param \Buzzi\Publish\Helper\DataBuilder\Customer $dataBuilderCustomer
     * @param \Magento\Customer\Model\CustomerRegistry $customerRegistry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Event\ManagerInterface $eventDispatcher
     */
    public function __construct(
        \Buzzi\Publish\Helper\DataBuilder\Base $dataBuilderBase,
        \Buzzi\Publish\Helper\DataBuilder\Customer $dataBuilderCustomer,
        \Magento\Customer\Model\CustomerRegistry $customerRegistry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Event\ManagerInterface $eventDispatcher
    ) {
        $this->dataBuilderBase = $dataBuilderBase;
        $this->dataBuilderCustomer = $dataBuilderCustomer;
        $this->customerRegistry = $customerRegistry;
        $this->storeManager = $storeManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param string $eventType
     * @return mixed[]
     */
    public function getPayload(Subscriber $subscriber, $eventType)
    {
        $payload = $this->dataBuilderBase->initBaseData($eventType);

        $payload['site_id'] = $this->getWebsiteCode($subscriber->getStoreId());

        $payload['customer'] = $subscriber->getCustomerId()
            ? $this->getCustomerData($subscriber->getCustomerId())
            : ['email' => $subscriber->getEmail()];

        $transport = new DataObject(['subscriber' => $subscriber, 'payload' => $payload, 'eventType' => $eventType]);
        $this->eventDispatcher->dispatch('buzzi_publish_newsletter_subscribe_payload', ['transport' => $transport]);

        return (array)$transport->getData('payload');
    }

    /**
     * @param int $customerId
     * @return string[]
     */
    private function getCustomerData($customerId)
    {
        $customer = $this->customerRegistry->retrieve($customerId);
        return $this->dataBuilderCustomer->getCustomerData($customer);
    }

    /**
     * @param int $storeId
     * @return string
     */
    private function getWebsiteCode($storeId)
    {
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        return $this->storeManager->getWebsite($websiteId)->getCode();
    }
}
