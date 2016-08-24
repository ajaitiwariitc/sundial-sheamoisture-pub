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

namespace ParadoxLabs\Subscriptions\Api\Data;

/**
 * Subscription data storage and processing
 */
interface SubscriptionInterface
{
    /**
     * Get subscription ID.
     *
     * @return int
     */
    public function getId();

    /**
     * Set source quote
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return $this
     */
    public function setQuote(\Magento\Quote\Api\Data\CartInterface $quote);

    /**
     * Get subscription quote
     *
     * @return \Magento\Quote\Api\Data\CartInterface
     */
//    public function getQuote();

    /**
     * Set source quote ID
     *
     * @param int|null $quoteId
     * @return $this
     */
    public function setQuoteId($quoteId);

    /**
     * Get source quote ID
     *
     * @return int
     */
    public function getQuoteId();

    /**
     * Set subscription frequency count
     *
     * @param int $frequencyCount
     * @return $this
     */
    public function setFrequencyCount($frequencyCount);

    /**
     * Set subscription frequency unit
     *
     * @param string $frequencyUnit
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setFrequencyUnit($frequencyUnit);

    /**
     * Set subscription length (number of billings to last for)
     *
     * @param int $length
     * @return $this
     */
    public function setLength($length);

    /**
     * Set subscription description. This will typically (but not necessarily) be the item name.
     *
     * @param string $description
     * @return $this
     */
    public function setDescription($description);

    /**
     * Get subscription description. This will typically (but not necessarily) be the item name.
     *
     * @return string
     */
    public function getDescription();

    /**
     * Set subscription subtotal. Mostly for record's sake; actual amount is handled by the quote.
     *
     * @param float $subtotal
     * @return $this
     */
    public function setSubtotal($subtotal);

    /**
     * Associate a given order with the subscription, and record the transaction details.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param string|null $message
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function recordBilling(\Magento\Sales\Api\Data\OrderInterface $order, $message = null);

    /**
     * Set subscription status.
     *
     * @param string $newStatus
     * @param string $message Message to log for the change (optional)
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setStatus($newStatus, $message = null);

    /**
     * Calculate and set next run date for the subscription.
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function calculateNextRun();

    /**
     * Set subscription customer ID
     *
     * @param $customerId
     * @return $this
     */
    public function setCustomerId($customerId);

    /**
     * Get subscription customer ID
     *
     * @return int|null
     */
    public function getCustomerId();

    /**
     * Get created-at date.
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Get updated-at date.
     *
     * @return string
     */
    public function getUpdatedAt();

    /**
     * Increment run_count by one.
     *
     * @return $this
     */
    public function incrementRunCount();

    /**
     * Set subscription store ID
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId);

    /**
     * Get subscription store ID.
     *
     * @return int
     */
    public function getStoreId();

    /**
     * Set the next run date for the subscription.
     *
     * @param string|int $nextRun Next run date (date or timestamp)
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setNextRun($nextRun);

    /**
     * Get next-run date.
     *
     * @return string
     */
    public function getNextRun();

    /**
     * Get last-run date.
     *
     * @return string
     */
    public function getLastRun();

    /**
     * Get subscription subtotal.
     *
     * @return float
     */
    public function getSubtotal();

    /**
     * Check whether subscription has billed to the prescribed length.
     *
     * @return bool
     */
    public function isComplete();

    /**
     * Get subscription length.
     *
     * @return int
     */
    public function getLength();

    /**
     * Get number of times the subscription has run.
     *
     * @return int
     */
    public function getRunCount();

    /**
     * Get subscription status.
     *
     * @return string
     */
    public function getStatus();

    /**
     * Set last_run to the current datetime.
     *
     * @return $this
     */
    public function updateLastRunTime();

    /**
     * Get subscription frequency count
     *
     * @return int
     */
    public function getFrequencyCount();

    /**
     * Get subscription frequency unit
     *
     * @return string
     */
    public function getFrequencyUnit();

    /**
     * Add a new log to the subscription.
     *
     * @param string $message
     * @param string $incrementId
     * @return $this
     */
    public function addLog($message, $incrementId = null);

    /**
     * Get additional information.
     *
     * If $key is set, will return that value or null; otherwise, will return an array of all additional date.
     *
     * @param string|null $key
     * @return mixed|null
     */
    public function getAdditionalInformation($key = null);

    /**
     * Set additional information.
     *
     * Can pass in a key-value pair to set one value, or a single parameter (associative array) to overwrite all data.
     *
     * @param string $key
     * @param string|null $value
     * @return $this
     */
    public function setAdditionalInformation($key, $value = null);
}
