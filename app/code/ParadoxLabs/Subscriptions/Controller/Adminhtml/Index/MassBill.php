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

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Class MassBill
 */
class MassBill extends \Magento\Backend\App\Action
{
    /**
     * @var string
     */
    protected $redirectUrl = '*/*/';

    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $filter;

    /**
     * @var object
     */
    protected $collectionFactory;

    /**
     * @var \ParadoxLabs\Subscriptions\Model\Service\Subscription
     */
    protected $subscriptionService;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param \ParadoxLabs\Subscriptions\Model\ResourceModel\Subscription\CollectionFactory $collectionFactory
     * @param \ParadoxLabs\Subscriptions\Model\Service\Subscription $subscriptionService
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        Filter $filter,
        \ParadoxLabs\Subscriptions\Model\ResourceModel\Subscription\CollectionFactory $collectionFactory,
        \ParadoxLabs\Subscriptions\Model\Service\Subscription $subscriptionService,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);

        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->subscriptionService = $subscriptionService;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            return $this->massAction($collection);
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath($this->redirectUrl);
        }
    }

    /**
     * Return component referer url, or something
     *
     * @return null|string
     */
    protected function getComponentRefererUrl()
    {
        return $this->filter->getComponentRefererUrl()?: 'subscriptions/index/';
    }

    /**
     * Bill selected subscriptions
     *
     * @param AbstractDb $collection
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    protected function massAction(AbstractDb $collection)
    {
        $groups       = [];
        $countBilled  = 0;
        $countFailed  = 0;
        $countSkipped = 0;

        $combine = $this->scopeConfig->getValue(
            'subscriptions/general/group_same_day',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        /** @var \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface $subscription */
        foreach ($collection->getItems() as $subscription) {
            if ($subscription->getStatus() != \ParadoxLabs\Subscriptions\Model\Source\Status::STATUS_ACTIVE) {
                $countSkipped++;
                continue;
            }

            if ($combine == 1) {
                $key = $this->subscriptionService->hashFulfillmentInfo($subscription);

                if (!isset($groups[$key])) {
                    $groups[$key] = [];
                }

                $groups[$key][] = $subscription;
            } else {
                $success = $this->subscriptionService->generateOrder([$subscription]);

                if ($success === true) {
                    $countBilled++;
                }
            }
        }

        /**
         * Bill each group, if we're doing that.
         */
        if ($combine && !empty($groups)) {
            foreach ($groups as $key => $group) {
                $success = $this->subscriptionService->generateOrder($group);

                if ($success === true) {
                    $countBilled += count($group);
                } else {
                    $countFailed += count($group);
                }
            }
        }

        if ($countSkipped && $countBilled) {
            $this->messageManager->addError(__('%1 subscription(s) cannot be billed.', $countSkipped));
        } elseif ($countSkipped) {
            $this->messageManager->addError(__('You cannot bill the selected subscription(s).'));
        }

        if ($countBilled) {
            $this->messageManager->addSuccess(
                __(
                    'We billed %1 subscription(s) successfully. %2 failed.',
                    $countBilled,
                    $countFailed
                )
            );
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());
        return $resultRedirect;
    }
}
