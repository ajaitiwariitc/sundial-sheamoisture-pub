<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <info@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\Subscriptions\Controller\Subscriptions;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * EditPost Class
 */
class EditPost extends Edit
{
    /**
     * @var \ParadoxLabs\Subscriptions\Model\Service\Subscription
     */
    protected $subscriptionService;

    /**
     * EditPost constructor.
     *
     * @param Context $context
     * @param Session\Proxy $customerSession
     * @param PageFactory $resultPageFactory
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Framework\Registry $registry
     * @param \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \ParadoxLabs\TokenBase\Helper\Address $addressHelper
     * @param \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterfaceFactory $subscriptionFactory
     * @param \Magento\Customer\Helper\Session\CurrentCustomer\Proxy $currentCustomer
     * @param \ParadoxLabs\Subscriptions\Model\Service\Subscription $subscriptionService
     */
    public function __construct(
        Context $context,
        Session\Proxy $customerSession,
        PageFactory $resultPageFactory,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\Registry $registry,
        \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \ParadoxLabs\TokenBase\Helper\Address $addressHelper,
        \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterfaceFactory $subscriptionFactory,
        \Magento\Customer\Helper\Session\CurrentCustomer\Proxy $currentCustomer,
        \ParadoxLabs\Subscriptions\Model\Service\Subscription $subscriptionService
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $resultPageFactory,
            $formKeyValidator,
            $registry,
            $cardFactory,
            $helper,
            $addressHelper,
            $subscriptionFactory,
            $currentCustomer
        );

        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Subscriptions edit page
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $initialized    = $this->initModels();

        if ($initialized !== true) {
            $resultRedirect->setPath('*/*/index');
            return $resultRedirect;
        }

        /** @var \ParadoxLabs\Subscriptions\Model\Subscription $subscription */
        $subscription = $this->registry->registry('current_subscription');

        try {
            $data         = $this->getRequest()->getParams();

            /** @var \Magento\Quote\Model\Quote $quote */
            $quote        = $subscription->getQuote();

            /**
             * Update payment
             */
            $this->subscriptionService->changePaymentId($subscription, $data['tokenbase_id']);

            /**
             * Update shipping address
             */
            if ($quote->getIsVirtual() == false) {
                $this->subscriptionService->changeShippingAddress($subscription, $data['shipping']);
            }

            $subscription->save();
        } catch (\Exception $e) {
            $this->helper->log('subscriptions', (string)$e);
            $this->messageManager->addError(__('ERROR: %1', $e->getMessage()));
        }

        $resultRedirect->setPath('*/*/view', ['id' => $subscription->getId()]);
        return $resultRedirect;
    }
}
