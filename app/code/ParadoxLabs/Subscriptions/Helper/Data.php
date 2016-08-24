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

namespace ParadoxLabs\Subscriptions\Helper;

/**
 * General helper
 */
class Data extends \ParadoxLabs\TokenBase\Helper\Operation
{
    /**
     * @var \Magento\Catalog\Helper\Product\Configuration
     */
    protected $productConfig;

    /**
     * @var array
     */
    protected $quoteContainsSubscription = [];

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \ParadoxLabs\TokenBase\Model\Logger\Logger $tokenbaseLogger
     * @param \Magento\Catalog\Helper\Product\Configuration\Proxy $productConfig
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \ParadoxLabs\TokenBase\Model\Logger\Logger $tokenbaseLogger,
        \Magento\Catalog\Helper\Product\Configuration\Proxy $productConfig
    ) {
        parent::__construct($context, $tokenbaseLogger);

        $this->productConfig = $productConfig;
    }

    /**
     * Check whether the given item should be a subscription.
     *
     * @param \Magento\Framework\Model\AbstractExtensibleModel $item
     * @return bool
     */
    public function isItemSubscription(\Magento\Framework\Model\AbstractExtensibleModel $item)
    {
        /** @var \Magento\Sales\Model\Order\Item|\Magento\Quote\Model\Quote\Item $item */
        if ($item->getProduct()->getData('subscription_active') == 1) {
            /**
             * Check for chosen interval
             */
            if ($this->getItemSubscriptionInterval($item) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the subscription interval (if any) for the current item. 0 for none.
     *
     * @param \Magento\Framework\Model\AbstractExtensibleModel $item
     * @return int
     */
    public function getItemSubscriptionInterval(\Magento\Framework\Model\AbstractExtensibleModel $item)
    {
        /** @var \Magento\Sales\Model\Order\Item|\Magento\Quote\Model\Quote\Item $item */

        /**
         * Check for chosen interval
         */
        if ($item instanceof \Magento\Quote\Model\Quote\Item) {
            $options = $this->productConfig->getCustomOptions($item);
        } else {
            $options = $item->getProductOptions();
            $options = isset($options['options']) ? $options['options'] : [];
        }

        if (is_array($options)) {
            foreach ($options as $option) {
                if ($option['label'] == $this->getSubscriptionLabel()) {
                    preg_match("/(\d+) /", $option['value'], $matches);

                    $oneString = (string)__('Every ' . $this->getItemSubscriptionUnit($item));

                    if (strpos($option['value'], $oneString) !== false) {
                        return 1;
                    } elseif (isset($matches[1]) && $matches[1] > 0) {
                        return intval($matches[1]);
                    }
                }
            }
        }

        return 0;
    }

    /**
     * Get the subscription unit for the current item.
     *
     * @param \Magento\Framework\Model\AbstractExtensibleModel $item
     * @return string
     */
    public function getItemSubscriptionUnit(\Magento\Framework\Model\AbstractExtensibleModel $item)
    {
        /** @var \Magento\Sales\Model\Order\Item|\Magento\Quote\Model\Quote\Item $item */
        return $item->getProduct()->getData('subscription_unit');
    }

    /**
     * Get the subscription length for the current item--number of billing cycles to be run. 0 for indefinite.
     *
     * @param \Magento\Framework\Model\AbstractExtensibleModel $item
     * @return int
     */
    public function getItemSubscriptionLength(\Magento\Framework\Model\AbstractExtensibleModel $item)
    {
        /** @var \Magento\Sales\Model\Order\Item|\Magento\Quote\Model\Quote\Item $item */
        return (int)$item->getProduct()->getData('subscription_length');
    }

    /**
     * Get the subscription description for the given item.
     *
     * @param \Magento\Framework\Model\AbstractExtensibleModel $item
     * @return string
     */
    public function getItemSubscriptionDesc(\Magento\Framework\Model\AbstractExtensibleModel $item)
    {
        /** @var \Magento\Sales\Model\Order\Item|\Magento\Quote\Model\Quote\Item $item */
        return $item->getName();
    }

    /**
     * Calculate initial price for a subscription item.
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return float
     */
    public function calculateInitialSubscriptionPrice(\Magento\Quote\Model\Quote\Item $item)
    {
        $product = $item->getProduct();
        $price   = $product->getFinalPrice();

        // Take subscription price to start (if any); otherwise, use normal product price.
        if ($product->getData('subscription_price') != '') {
            $price = max(0, (float)$product->getData('subscription_price'));
        }

        // Add the initial adjustment fee (if any)
        if ($product->getData('subscription_init_adjustment') != '') {
            $price = max(0, $price + (float)$product->getData('subscription_init_adjustment'));
        }

        return $price;
    }

    /**
     * Calculate regular price for a subscription item.
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return float
     */
    public function calculateRegularSubscriptionPrice(\Magento\Quote\Model\Quote\Item $item)
    {
        $product = $item->getProduct();
        $price   = $product->getFinalPrice();

        // Take subscription price to start (if any); otherwise, use normal product price.
        if ($product->getData('subscription_price') != '') {
            $price = max(0, (float)$product->getData('subscription_price'));
        }

        return $price;
    }

    /**
     * Check whether the given quote contains a subscription item.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return bool
     */
    public function quoteContainsSubscription($quote)
    {
        if (($quote instanceof \Magento\Quote\Api\Data\CartInterface) !== true) {
            return false;
        }

        if ($quote->getId() && isset($this->quoteContainsSubscription[$quote->getId()])) {
            return $this->quoteContainsSubscription[$quote->getId()];
        } else {
            /** @var \Magento\Quote\Model\Quote\Item $item */
            foreach ($quote->getAllItems() as $item) {
                if ($this->isItemSubscription($item) === true) {
                    if ($quote->getId()) {
                        $this->quoteContainsSubscription[$quote->getId()] = true;
                    }

                    return true;
                }
            }

            if ($quote->getId()) {
                $this->quoteContainsSubscription[$quote->getId()] = false;
            }
        }

        return false;
    }

    /**
     * Get label for the subscription custom option. Poor attempt at flexibility/localization.
     *
     * @return string
     */
    public function getSubscriptionLabel()
    {
        return (string)__(
            $this->scopeConfig->getValue(
                'subscriptions/general/option_label',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        );
    }

    /**
     * Check whether subscriptions module is enabled in configuration for the current scope.
     *
     * @return bool
     */
    public function moduleIsActive()
    {
        return $this->scopeConfig->getValue(
            'subscriptions/general/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) ? true : false;
    }
}
