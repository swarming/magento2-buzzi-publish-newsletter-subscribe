<?php
/**
 * Copyright © Swarming Technology, LLC. All rights reserved.
 */
namespace Buzzi\PublishNewsletterSubscribe\Service;

use Magento\Newsletter\Model\Subscriber as NewsletterSubscriber;
use Buzzi\PublishNewsletterSubscribe\Model\DataBuilder;

class BulkSender
{
    /**
     * @var \Buzzi\Publish\Api\QueueInterface
     */
    private $queue;

    /**
     * @var \Buzzi\PublishNewsletterSubscribe\Model\DataBuilder
     */
    private $dataBuilder;

    /**
     * @var \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory
     */
    private $subscribersCollectionFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $pageSize;

    /**
     * @param \Buzzi\Publish\Api\QueueInterface $queue
     * @param \Buzzi\PublishNewsletterSubscribe\Model\DataBuilder $dataBuilder
     * @param \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscribersCollectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param int $pageSize
     */
    public function __construct(
        \Buzzi\Publish\Api\QueueInterface $queue,
        \Buzzi\PublishNewsletterSubscribe\Model\DataBuilder $dataBuilder,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscribersCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger,
        $pageSize = 1000
    ) {
        $this->queue = $queue;
        $this->dataBuilder = $dataBuilder;
        $this->subscribersCollectionFactory = $subscribersCollectionFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->pageSize = (int)$pageSize;
    }

    /**
     * @param string|null $websiteCode
     * @return void
     */
    public function sendAllSubscribers($websiteCode = null)
    {
        $subscribersCollection = $this->subscribersCollectionFactory->create();
        $this->initFilters($subscribersCollection, $websiteCode);

        $subscribersCollection->setPageSize($this->pageSize);
        $lastPage = $subscribersCollection->getLastPageNumber();
        $pageNumber = 1;
        do {
            $subscribersCollection->clear();
            $subscribersCollection->setCurPage($pageNumber);

            $this->sendSubscribers($subscribersCollection->getItems());

            $pageNumber++;
        } while ($pageNumber <= $lastPage);
    }

    /**
     * @param \Magento\Newsletter\Model\ResourceModel\Subscriber\Collection $subscribersCollection
     * @param string $websiteCode
     * @return void
     */
    private function initFilters($subscribersCollection, $websiteCode = null)
    {
        $subscribersCollection->useOnlySubscribed();

        if ($websiteCode) {
            $storeIds = $this->storeManager->getWebsite($websiteCode)->getStoreIds();
            $subscribersCollection->addStoreFilter($storeIds);
        }
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber[] $subscribers
     * @return void
     */
    private function sendSubscribers($subscribers)
    {
        foreach ($subscribers as $subscriber) {
            $this->addToQueue($subscriber);
        }
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @return void
     */
    private function addToQueue(NewsletterSubscriber $subscriber)
    {
        try {
            $payload = $this->dataBuilder->getPayload($subscriber, DataBuilder::EVENT_TYPE_SUBSCRIBE);
            $this->queue->add(DataBuilder::EVENT_TYPE_SUBSCRIBE, $payload, $subscriber->getStoreId());
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
