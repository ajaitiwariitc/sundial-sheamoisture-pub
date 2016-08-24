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

namespace ParadoxLabs\Subscriptions\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;

/**
 * Save Class
 */
class Save extends View
{
    /**
     * @var \ParadoxLabs\Subscriptions\Model\Service\Subscription
     */
    protected $subscriptionService;

    /**
     * Save constructor.
     *
     * @param Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $registry
     * @param \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterfaceFactory $subscriptionFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \ParadoxLabs\Subscriptions\Helper\Data $helper
     * @param \ParadoxLabs\Subscriptions\Model\Service\Subscription $subscriptionService
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterfaceFactory $subscriptionFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \ParadoxLabs\Subscriptions\Helper\Data $helper,
        \ParadoxLabs\Subscriptions\Model\Service\Subscription $subscriptionService
    ) {
        parent::__construct(
            $context,
            $resultPageFactory,
            $registry,
            $subscriptionFactory,
            $customerFactory,
            $resultLayoutFactory,
            $helper
        );

        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Subscription save action
     *
     * @return \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        $initialized    = $this->initModels();
        $resultRedirect = $this->resultRedirectFactory->create();

        /**
         * If we were not able to load the model, short-circuit.
         */
        if ($initialized !== true) {
            $resultRedirect->setRefererOrBaseUrl();
            return $resultRedirect;
        }

        /** @var \ParadoxLabs\Subscriptions\Model\Subscription $subscription */
        $subscription = $this->registry->registry('current_subscription');

        try {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote        = $subscription->getQuote();

            $data         = $this->getRequest()->getParams();

            /**
             * Update subscription details
             */
            $subscription->setFrequencyCount($data['frequency_count']);
            $subscription->setFrequencyUnit($data['frequency_unit']);
            $subscription->setLength($data['length']);
            $subscription->setDescription($data['description']);
            $subscription->setNextRun($data['next_run']);

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

            $subscription->addRelatedObject($quote, true);
            $subscription->save();

            $this->messageManager->addSuccess(__('Subscription saved.', $subscription->getId()));

            if ($this->getRequest()->getParam('back', false)) {
                $resultRedirect->setPath('*/*/view', ['entity_id' => $subscription->getId(), '_current' => true]);
            } else {
                $resultRedirect->setPath('*/*/index');
            }
        } catch (\Exception $e) {
            $this->helper->log('subscriptions', (string)$e);
            $this->messageManager->addError(__('ERROR: %1', $e->getMessage()));

            $resultRedirect->setPath('*/*/view', ['entity_id' => $subscription->getId(), '_current' => true]);
        }

        return $resultRedirect;
    }
}
