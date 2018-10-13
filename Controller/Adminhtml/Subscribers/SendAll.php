<?php
/**
 * Copyright © Swarming Technology, LLC. All rights reserved.
 */
namespace Buzzi\PublishNewsletterSubscribe\Controller\Adminhtml\Subscribers;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

class SendAll extends Action
{
    const FULL_ACTION_NAME = 'buzzi_newsletter_subscribe/subscribers/sendAll';

    /**
     * @var \Buzzi\PublishNewsletterSubscribe\Service\BulkSender
     */
    private $bulkSender;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Buzzi\PublishNewsletterSubscribe\Service\BulkSender $bulkSender
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Buzzi\PublishNewsletterSubscribe\Service\BulkSender $bulkSender
    ) {
        $this->bulkSender = $bulkSender;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $response = [
            'status' => 'success',
            'message' => __('All subscribers were were added to queue and will be sent shortly.')
        ];

        $websiteCode = $this->getRequest()->getParam('website') ?: null;

        try {

            $this->bulkSender->sendAllSubscribers($websiteCode);
        } catch (\Exception $e) {
            $response['status'] = 'fail';
            $response['message'] = $e->getMessage();
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($response);
        return $resultJson;
    }
}
