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

namespace ParadoxLabs\Subscriptions\Model;

use ParadoxLabs\Subscriptions\Api\Data\LogInterface;

/**
 * Subscription log - change record
 */
class Log extends \Magento\Framework\Model\AbstractModel implements LogInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'paradoxlabs_subscription_log';

    /**
     * @var string
     */
    protected $_eventObject = 'log';

    /**
     * @var Source\Status
     */
    protected $statusSource;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $backendSession;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param Source\Status $statusSource
     * @param \Magento\Backend\Model\Auth\Session\Proxy $backendSession
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \ParadoxLabs\Subscriptions\Model\Source\Status $statusSource,
        \Magento\Backend\Model\Auth\Session\Proxy $backendSession,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->statusSource = $statusSource;
        $this->backendSession = $backendSession;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Set subscription log is associated to.
     *
     * @param Subscription $subscription
     * @return $this
     */
    public function setSubscription(Subscription $subscription)
    {
        $this->setData('subscription_id', $subscription->getId());

        return $this;
    }

    /**
     * Set subscription status.
     *
     * @param string $newStatus
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setStatus($newStatus)
    {
        if ($this->statusSource->isAllowedStatus($newStatus)) {
            $this->setData('status', $newStatus);
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid status "%1"', $newStatus));
        }

        return $this;
    }

    /**
     * Get subscription status.
     *
     * @return string $this
     */
    public function getStatus()
    {
        return $this->getData('status');
    }

    /**
     * Set associated order Increment ID.
     *
     * We save increment ID rather than order ID because the order has not yet been saved when subscription
     * generation occurs. No ID to be had in that case.
     *
     * @param string $orderIncrementId
     * @return $this
     */
    public function setOrderIncrementId($orderIncrementId)
    {
        return $this->setData('order_increment_id', $orderIncrementId);
    }

    /**
     * Get associated order increment ID.
     *
     * @return string
     */
    public function getOrderIncrementId()
    {
        return $this->getData('order_increment_id');
    }

    /**
     * Set log message.
     *
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        return $this->setData('description', $description);
    }

    /**
     * Get log message.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->getData('description');
    }

    /**
     * Model construct that should be used for object initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('ParadoxLabs\Subscriptions\Model\ResourceModel\Log');
    }

    /**
     * Finalize before saving.
     *
     * @return $this
     */
    public function beforeSave()
    {
        parent::beforeSave();

        if ($this->isObjectNew()) {
            /**
             * Set date.
             */
            $now = new \DateTime('@' . time());
            $this->setData('created_at', $now->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));

            /**
             * Set agent (if any).
             */
            if ($this->hasData('agent_id') == false) {
                $this->determineAgent();
            }
        }

        return $this;
    }

    /**
     * Attempt to determine whether this action was triggered by the customer, an admin, or neither. Result is stored
     * with the log.
     *
     * @return $this
     */
    protected function determineAgent()
    {
        if ($this->_appState->getAreaCode() == \Magento\Framework\App\Area::AREA_FRONTEND) {
            // Frontend: Customer action.
            $this->setAgentId(-1);
        } elseif ($this->_appState->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
            // Admin: Admin user action (record who).
            $this->setAgentId($this->backendSession->getUser()->getId());
        } else {
            // Other: Don't know, or none. API, cron, etc.
            $this->setAgentId(0);
        }

        return $this;
    }

    /**
     * Set ID of agent responsible for the logged action. admin user_id, or -1 for customer.
     *
     * @param int $agentId
     * @return $this
     */
    public function setAgentId($agentId)
    {
        return $this->setData('agent_id', $agentId);
    }

    /**
     * Get ID of agent responsible for the logged action. admin user_id, or -1 for customer.
     *
     * @return int
     */
    public function getAgentId()
    {
        return $this->getData('agent_id');
    }

    /**
     * Get created-at date.
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }
}