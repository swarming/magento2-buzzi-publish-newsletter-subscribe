<?php
/**
 * Copyright © Swarming Technology, LLC. All rights reserved.
 */
namespace Buzzi\PublishNewsletterSubscribe\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Buzzi\PublishNewsletterSubscribe\Controller\Adminhtml\Subscribers\SendAll as ControllerSendAllSubscribers;

class SendAllSubscribers extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var string
     */
    protected $_template = 'system/config/send_all_subscribers.phtml';

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setHtmlId($element->getHtmlId());
        return $this->_toHtml();
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()
            ->unsCanUseWebsiteValue()
            ->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getButtonLabel()
    {
        return __('Send All Subscribers');
    }

    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->_urlBuilder->getUrl(ControllerSendAllSubscribers::FULL_ACTION_NAME);
    }

    /**
     * @return string
     */
    public function getWebsiteCode()
    {
        return $this->getRequest()->getParam('website', '');
    }
}
