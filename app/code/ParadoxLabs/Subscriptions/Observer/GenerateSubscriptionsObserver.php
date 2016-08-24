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

namespace ParadoxLabs\Subscriptions\Observer;

use \ParadoxLabs\Subscriptions\Model\Subscription;
use \ParadoxLabs\Subscriptions\Model\Source\Status;

/**
 * GenerateSubscriptionsObserver Class
 */
class GenerateSubscriptionsObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \ParadoxLabs\Subscriptions\Helper\Data
     */
    protected $helper;

    /**
     * @var \ParadoxLabs\Subscriptions\Model\SubscriptionFactory
     */
    protected $subscriptionFactory;

    /**
     * @var \Magento\Quote\Api\Data\CartInterfaceFactory
     */
    protected $quoteFactory;

    /**
     * @var \Magento\Quote\Api\Data\AddressInterfaceFactory
     */
    protected $quoteAddressFactory;

    /**
     * @var \Magento\Framework\DataObject\Copy
     */
    protected $objectCopyService;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * GenerateSubscriptionsObserver constructor.
     *
     * @param \ParadoxLabs\Subscriptions\Helper\Data $helper
     * @param \ParadoxLabs\Subscriptions\Model\SubscriptionFactory $subscriptionFactory
     * @param \Magento\Quote\Api\Data\CartInterfaceFactory $quoteFactory
     * @param \Magento\Quote\Api\Data\AddressInterfaceFactory $quoteAddressFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     */
    public function __construct(
        \ParadoxLabs\Subscriptions\Helper\Data $helper,
        \ParadoxLabs\Subscriptions\Model\SubscriptionFactory $subscriptionFactory,
        \Magento\Quote\Api\Data\CartInterfaceFactory $quoteFactory,
        \Magento\Quote\Api\Data\AddressInterfaceFactory $quoteAddressFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\DataObject\Copy $objectCopyService
    ) {
        $this->helper = $helper;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->quoteFactory = $quoteFactory;
        $this->quoteAddressFactory = $quoteAddressFactory;
        $this->customerRepository = $customerRepository;
        $this->objectCopyService = $objectCopyService;
    }

    /**
     * Create subscriptions as needed on order place.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @throws \Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->helper->moduleIsActive() !== true) {
            return;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getData('order');

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();

        // Ensure we don't end up generating new subscriptions from existing ones.
        if ($payment->getAdditionalInformation('is_subscription_generated') == 1) {
            return;
        }

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            if ($this->helper->isItemSubscription($item) === true) {
                /**
                 * For each active subscription item,
                 * Create a matching quote
                 * Initialize an associated subscription
                 */

                try {
                    $subscription = $this->generateSubscription($order, $item);

                    $message = __(
                        'Subscription created. Initial order total: %1',
                        $order->formatPriceTxt($order->getGrandTotal())
                    );

                    $subscription->recordBilling($order, $message);
                    $subscription->save();
                } catch (\Exception $e) {
                    $this->helper->log('subscriptions', (string)$e);

                    throw $e;
                }
            }
        }
    }

    /**
     * Create a subscription for the given item.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param \Magento\Sales\Api\Data\OrderItemInterface $item
     * @return Subscription
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function generateSubscription(
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Magento\Sales\Api\Data\OrderItemInterface $item
    ) {
        /** @var \Magento\Sales\Model\Order\Item $item  */

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->generateSubscriptionQuote($order, $item);

        /** @var Subscription $subscription */
        $subscription = $this->subscriptionFactory->create();

        $subscription->setStoreId($quote->getStoreId());
        $subscription->setStatus(Status::STATUS_ACTIVE);
        $subscription->setCustomerId($quote->getCustomerId());
        $subscription->setQuote($quote);
        $subscription->setFrequencyCount($this->helper->getItemSubscriptionInterval($item));
        $subscription->setFrequencyUnit($this->helper->getItemSubscriptionUnit($item));
        $subscription->setLength($this->helper->getItemSubscriptionLength($item));
        $subscription->setDescription($this->helper->getItemSubscriptionDesc($item));
        $subscription->setSubtotal($quote->getBaseSubtotal());
        $subscription->calculateNextRun();

        $subscription->addRelatedObject($quote, true);

        return $subscription;
    }

    /**
     * Create a subscription base quote for the given item.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param \Magento\Sales\Api\Data\OrderItemInterface $item
     * @return \Magento\Quote\Api\Data\CartInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function generateSubscriptionQuote(
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Magento\Sales\Api\Data\OrderItemInterface $item
    ) {
        /**
         * Initialize objects
         */

        /** @var \Magento\Sales\Model\Order\Item $item */
        /** @var \Magento\Sales\Model\Order $order */

        /** @var \Magento\Quote\Model\Quote $orderQuote */
        $orderQuote = $this->quoteFactory->create();
        $orderQuote->load($order->getQuoteId());

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteFactory->create();

        /**
         * Duplicate billing address
         */

        /** @var \Magento\Quote\Model\Quote\Address $billingAddress */
        $billingAddress = $this->quoteAddressFactory->create();

        $this->objectCopyService->copyFieldsetToTarget(
            'sales_copy_order_billing_address',
            'to_order',
            $orderQuote->getBillingAddress(),
            $billingAddress
        );

        /**
         * Duplicate shipping address
         */

        /** @var \Magento\Quote\Model\Quote\Address $shippingAddress */
        $shippingAddress = $this->quoteAddressFactory->create();

        $this->objectCopyService->copyFieldsetToTarget(
            'sales_copy_order_shipping_address',
            'to_order',
            $orderQuote->getShippingAddress(),
            $shippingAddress
        );

        /**
         * Duplicate payment object
         */

        $this->objectCopyService->copyFieldsetToTarget(
            'sales_convert_order_payment',
            'to_quote_payment',
            $order->getPayment(),
            $quote->getPayment()
        );

        $quote->getPayment()->setId(null);
        $quote->getPayment()->setQuoteId(null);

        /**
         * Create the quote
         */

        // Try to load and set customer.
        $customerId = $order->getCustomerId();

        if ($customerId > 0) {
            try {
                $customer = $this->customerRepository->getById($customerId);

                $quote->assignCustomer($customer);
            } catch (\Exception $e) {
                // Ignore missing customer error
            }
        }
        
        // Set a far-off quote updated date to avoid pruning. This is the highest Magento allows (timestamp).
        $updatedAt = new \DateTime('2038-01-01');

        $quote->setStoreId($order->getStoreId())
            ->setIsMultiShipping(false)
            ->setIsActive(false)
            ->setUpdatedAt($updatedAt->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT))
            ->setRemoteIp($order->getRemoteIp())
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress);

        $product = $item->getProduct();

        if (!$product->getId()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Could not find product for item %1 (%2)', $item->getSku(), $item->getId())
            );
        }

        /**
         * Set the product and price
         */
        $info = $item->getProductOptionByCode('info_buyRequest');
        $info = new \Magento\Framework\DataObject($info);
        $info->setData('qty', $item->getQtyOrdered());

        $quote->addProduct($product, $info);

        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $quote->getItemsCollection()->getFirstItem();

        $newPrice  = $this->helper->calculateRegularSubscriptionPrice($quoteItem);

        if ($newPrice != $product->getFinalPrice()) {
            $quoteItem->setOriginalCustomPrice($newPrice);
        }

        /**
         * Set shipping info
         */

        $quote->setIsVirtual($quote->getIsVirtual());

        $quote->getShippingAddress()->setCollectShippingRates(true)
                                    ->collectShippingRates();

        $quote->getShippingAddress()->setShippingMethod($order->getShippingMethod())
              ->setShippingDescription($order->getShippingDescription());

        $quote->collectTotals();

        return $quote;
    }
}
