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

use ParadoxLabs\Subscriptions\Model\Subscription;

/**
 * Subscription log - change record
 *
 * @api
 */
interface LogInterface
{
    /**
     * Set subscription log is associated to.
     *
     * @param Subscription $subscription
     * @return $this
     */
    public function setSubscription(Subscription $subscription);

    /**
     * Set subscription status.
     *
     * @param string $newStatus
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setStatus($newStatus);

    /**
     * Get subscription status.
     *
     * @return string $this
     */
    public function getStatus();

    /**
     * Set associated order increment ID.
     *
     * @param string $orderIncrementId
     * @return $this
     */
    public function setOrderIncrementId($orderIncrementId);

    /**
     * Get associated order increment ID.
     *
     * @return string
     */
    public function getOrderIncrementId();

    /**
     * Set log message.
     *
     * @param string $description
     * @return $this
     */
    public function setDescription($description);

    /**
     * Get log message.
     *
     * @return string
     */
    public function getDescription();

    /**
     * Set ID of agent responsible for the logged action. admin user_id, or -1 for customer.
     *
     * @param int $agentId
     * @return $this
     */
    public function setAgentId($agentId);

    /**
     * Get ID of agent responsible for the logged action. admin user_id, or -1 for customer.
     *
     * @return int
     */
    public function getAgentId();

    /**
     * Get created-at date.
     *
     * @return string
     */
    public function getCreatedAt();
}
