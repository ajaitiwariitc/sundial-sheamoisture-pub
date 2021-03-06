<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <magento@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\Subscriptions\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;

/**
 * Subscriptions form
 */
class View extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterfaceFactory
     */
    protected $subscriptionFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Framework\View\Result\LayoutFactory
     */
    protected $resultLayoutFactory;

    /**
     * @var \ParadoxLabs\Subscriptions\Helper\Data
     */
    protected $helper;

    /**
     * Index constructor.
     *
     * @param Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $registry
     * @param \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterfaceFactory $subscriptionFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \ParadoxLabs\Subscriptions\Helper\Data $helper
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterfaceFactory $subscriptionFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \ParadoxLabs\Subscriptions\Helper\Data $helper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->registry = $registry;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->customerFactory = $customerFactory;
        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->helper = $helper;

        parent::__construct($context);
    }

    /**
     * Subscriptions list action
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $initialized = $this->initModels();

        if ($initialized !== true) {
            $this->messageManager->addError(__('Could not load the requested subscription.'));

            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/index');
            return $resultRedirect;
        }

        $resultPage = $this->resultPageFactory->create();

        /**
         * Set active menu item
         */
        $resultPage->setActiveMenu('ParadoxLabs_Subscriptions::subscriptions_manage');
        $resultPage->getConfig()->getTitle()->prepend(__('Subscriptions'));

        /**
         * Add breadcrumb item
         */
        $resultPage->addBreadcrumb(__('Subscriptions'), __('Subscriptions'));
        $resultPage->addBreadcrumb(__('Manage Subscriptions'), __('Manage Subscriptions'));

        return $resultPage;
    }

    /**
     * Determine if authorized to perform these actions.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('ParadoxLabs_Subscriptions::subscriptions');
    }

    /**
     * Initialize subscription/customer models for the current request.
     *
     * @return bool Successful or not
     */
    protected function initModels()
    {
        /**
         * Load subscription by ID.
         */
        $id = (int)$this->getRequest()->getParam('entity_id');

        /** @var \ParadoxLabs\Subscriptions\Model\Subscription $subscription */
        $subscription = $this->subscriptionFactory->create();
        $subscription->load($id);

        /**
         * If it doesn't exist, fail (redirect to grid).
         */
        if ($id < 1 || $subscription->getId() != $id) {
            return false;
        }

        $this->registry->register('current_subscription', $subscription);

        /**
         * Load and set customer (if any) for TokenBase.
         */
        if ($subscription->getCustomerId() > 0) {
            $customer = $this->customerFactory->create();
            $customer->load($subscription->getCustomerId());

            if ($customer->getId() == $subscription->getCustomerId()) {
                $this->registry->register('current_customer', $customer);
            }
        }

        return true;
    }
}
