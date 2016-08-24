<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Model;

/**
 * Invitation data model
 *
 * @method \Magento\Invitation\Model\ResourceModel\Invitation _getResource()
 * @method \Magento\Invitation\Model\ResourceModel\Invitation getResource()
 * @method int getCustomerId()
 * @method \Magento\Invitation\Model\Invitation setCustomerId(int $value)
 * @method string getInvitationDate()
 * @method \Magento\Invitation\Model\Invitation setInvitationDate(string $value)
 * @method string getEmail()
 * @method \Magento\Invitation\Model\Invitation setEmail(string $value)
 * @method int getReferralId()
 * @method \Magento\Invitation\Model\Invitation setReferralId(int $value)
 * @method string getProtectionCode()
 * @method \Magento\Invitation\Model\Invitation setProtectionCode(string $value)
 * @method string getSignupDate()
 * @method \Magento\Invitation\Model\Invitation setSignupDate(string $value)
 * @method \Magento\Invitation\Model\Invitation setStoreId(int $value)
 * @method int getGroupId()
 * @method \Magento\Invitation\Model\Invitation setGroupId(int $value)
 * @method string getMessage()
 * @method \Magento\Invitation\Model\Invitation setMessage(string $value)
 * @method string getStatus()
 * @method \Magento\Invitation\Model\Invitation setStatus(string $value)
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Invitation extends \Magento\Framework\Model\AbstractModel
{
    const XML_PATH_EMAIL_IDENTITY = 'magento_invitation/email/identity';

    const XML_PATH_EMAIL_TEMPLATE = 'magento_invitation/email/template';

    /**
     * @var array
     */
    private static $_customerExistsLookup = [];

    /**
     * @var string
     */
    protected $_eventPrefix = 'magento_invitation';

    /**
     * @var string
     */
    protected $_eventObject = 'invitation';

    /**
     * Invitation data
     *
     * @var \Magento\Invitation\Helper\Data
     */
    protected $_invitationData;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Invitation Config
     *
     * @var \Magento\Invitation\Model\Config
     */
    protected $_config;

    /**
     * Invitation History Factory
     *
     * @var \Magento\Invitation\Model\Invitation\HistoryFactory
     */
    protected $_historyFactory;

    /**
     * Customer Factory
     *
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $mathRandom;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Invitation\Status
     */
    protected $status;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Invitation\Helper\Data $invitationData
     * @param \Magento\Invitation\Model\ResourceModel\Invitation $resource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Invitation\Model\Config $config
     * @param \Magento\Invitation\Model\Invitation\HistoryFactory $historyFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Invitation\Status $status
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Invitation\Helper\Data $invitationData,
        \Magento\Invitation\Model\ResourceModel\Invitation $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Invitation\Model\Config $config,
        \Magento\Invitation\Model\Invitation\HistoryFactory $historyFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Invitation\Status $status,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_invitationData = $invitationData;
        $this->_storeManager = $storeManager;
        $this->_config = $config;
        $this->_historyFactory = $historyFactory;
        $this->_customerFactory = $customerFactory;
        $this->_transportBuilder = $transportBuilder;
        $this->mathRandom = $mathRandom;
        $this->dateTime = $dateTime;
        $this->_scopeConfig = $scopeConfig;
        $this->status = $status;
    }

    /**
     * Intialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Magento\Invitation\Model\ResourceModel\Invitation');
    }

    /**
     * Store ID getter
     *
     * @return int
     */
    public function getStoreId()
    {
        if ($this->hasData('store_id')) {
            return $this->_getData('store_id');
        }
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * Load invitation by an encrypted code
     *
     * @param string $code
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadByInvitationCode($code)
    {
        $code = explode(':', $code, 2);
        if (count($code) != 2) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Please correct the invitation code.'));
        }
        list($id, $protectionCode) = $code;
        $this->load($id);
        if (!$this->getId() || $this->getProtectionCode() != $protectionCode) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Please correct the invitation code.'));
        }
        return $this;
    }

    /**
     * Model before save
     *
     * @return $this
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function beforeSave()
    {
        if (!$this->getId()) {
            // set initial data for new one
            $this->addData(
                [
                    'protection_code' => $this->mathRandom->getUniqueHash(),
                    'status' => Invitation\Status::STATUS_NEW,
                    'invitation_date' => $this->dateTime->formatDate(time()),
                    'store_id' => $this->getStoreId(),
                ]
            );
            $inviter = $this->getInviter();
            if ($inviter) {
                $this->setCustomerId($inviter->getId());
            }
            if ($this->_config->getUseInviterGroup()) {
                if ($inviter) {
                    $this->setGroupId($inviter->getGroupId());
                }
                if (!$this->hasGroupId()) {
                    throw new \Magento\Framework\Exception\InputException(
                        __('You need to specify a customer ID group.')
                    );
                }
            } else {
                $this->unsetData('group_id');
            }

            if (!(int)$this->getStoreId()) {
                throw new \Magento\Framework\Exception\InputException(__('The wrong store is specified.'));
            }
            $this->makeSureCustomerNotExists();
        } else {
            if ($this->dataHasChangedFor('message') && !$this->canMessageBeUpdated()) {
                throw new \Magento\Framework\Exception\InputException(
                    __('You can\'t update this message.')
                );
            }
        }
        return parent::beforeSave();
    }

    /**
     * Update status history after save
     *
     * @return $this
     */
    public function afterSave()
    {
        $this->_historyFactory->create()->setInvitationId($this->getId())->setStatus($this->getStatus())->save();
        $parent = parent::afterSave();
        if ($this->getStatus() === Invitation\Status::STATUS_NEW) {
            $this->setOrigData();
        }
        return $parent;
    }

    /**
     * Send invitation email
     *
     * @return bool
     */
    public function sendInvitationEmail()
    {
        $this->makeSureCanBeSent();
        $store = $this->_storeManager->getStore($this->getStoreId());

        $templateIdentifier = $this->_scopeConfig->getValue(
            self::XML_PATH_EMAIL_TEMPLATE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        $from = $this->_scopeConfig->getValue(
            self::XML_PATH_EMAIL_IDENTITY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        $this->_transportBuilder->setTemplateIdentifier(
            $templateIdentifier
        )->setTemplateOptions(
            ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->getStoreId()]
        )->setTemplateVars(
            [
                'url' => $this->_invitationData->getInvitationUrl($this),
                'message' => $this->getMessage(),
                'store' => $store,
                'store_name' => $store->getGroup()->getName(),
                'inviter_name' => $this->getInviter() ? $this->getInviter()->getName() : null,
            ]
        )->setFrom(
            $from
        )->addTo(
            $this->getEmail()
        );
        $transport = $this->_transportBuilder->getTransport();
        try {
            $transport->sendMessage();
        } catch (\Magento\Framework\Exception\MailException $e) {
            return false;
        }
        $this->setStatus(Invitation\Status::STATUS_SENT)->setUpdateDate(true)->save();
        return true;
    }

    /**
     * Get an encrypted invitation code
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getInvitationCode()
    {
        if (!$this->getId()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t generate encrypted code.'));
        }
        return $this->getId() . ':' . $this->getProtectionCode();
    }

    /**
     * Check and get customer if it was set
     *
     * @return \Magento\Customer\Model\Customer
     */
    public function getInviter()
    {
        $inviter = $this->getCustomer();
        if (!$inviter || !$inviter->getId()) {
            $inviter = null;
        }
        return $inviter;
    }

    /**
     * Check whether invitation can be sent
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function makeSureCanBeSent()
    {
        if (!$this->getId()) {
            throw new \Magento\Framework\Exception\InputException(
                __('We can\'t find an ID for this invitation.')
            );
        }
        if (!in_array($this->getStatus(), $this->status->getCanBeSentStatuses())) {
            throw new \Magento\Framework\Exception\InputException(
                __('You can\'t send an invitation with status "%1".', $this->getStatus())
            );
        }
        if (!$this->getEmail() || !\Zend_Validate::is($this->getEmail(), 'EmailAddress')) {
            throw new \Magento\Framework\Exception\InputException(
                __('Please enter a valid invitation email.')
            );
        }
        $this->makeSureCustomerNotExists();
    }

    /**
     * Check whether customer with specified email exists
     *
     * @param string $email
     * @param string $websiteId
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function makeSureCustomerNotExists($email = null, $websiteId = null)
    {
        if (null === $websiteId) {
            $websiteId = $this->_storeManager->getStore($this->getStoreId())->getWebsiteId();
        }
        if (!$websiteId) {
            throw new \Magento\Framework\Exception\InputException(__('We can\'t identify the proper website.'));
        }
        if (null === $email) {
            $email = $this->getEmail();
        }
        if (!$email) {
            throw new \Magento\Framework\Exception\InputException(__('Please specify an email.'));
        }

        // lookup customer by specified email/website id
        if (!isset(self::$_customerExistsLookup[$email]) || !isset(self::$_customerExistsLookup[$email][$websiteId])) {
            $customer = $this->_customerFactory->create()->setWebsiteId($websiteId)->loadByEmail($email);
            self::$_customerExistsLookup[$email][$websiteId] = $customer->getId() ? $customer->getId() : false;
        }
        if (false === self::$_customerExistsLookup[$email][$websiteId]) {
            return;
        }
        throw new \Magento\Framework\Exception\AlreadyExistsException(
            __('This invitation is addressed to a current customer: "%1".', $email)
        );
    }

    /**
     * Check whether this invitation can be accepted
     *
     * @param int|string $websiteId
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     */
    public function makeSureCanBeAccepted($websiteId = null)
    {
        $messageInvalid = __('This invitation is not valid.');
        if (!$this->getId()) {
            throw new \Magento\Framework\Exception\InputException($messageInvalid);
        }
        if (!in_array($this->getStatus(), $this->status->getCanBeAcceptedStatuses())) {
            throw new \Magento\Framework\Exception\InputException($messageInvalid);
        }
        if (null === $websiteId) {
            $websiteId = $this->_storeManager->getWebsite()->getId();
        }
        if ($websiteId != $this->_storeManager->getStore($this->getStoreId())->getWebsiteId()) {
            throw new \Magento\Framework\Exception\InputException($messageInvalid);
        }
    }

    /**
     * Check whether message can be updated
     *
     * @return bool
     */
    public function canMessageBeUpdated()
    {
        return (bool)(int)$this->getId() && $this->getStatus() === Invitation\Status::STATUS_NEW;
    }

    /**
     * Check whether invitation can be cancelled
     *
     * @return bool
     */
    public function canBeCanceled()
    {
        return (bool)(int)$this->getId() && in_array($this->getStatus(), $this->status->getCanBeCancelledStatuses());
    }

    /**
     * Check whether invitation can be sent. Will throw exception on invalid data.
     *
     * @return bool
     * @throws \Magento\Framework\Exception\InputException
     */
    public function canBeSent()
    {
        try {
            $this->makeSureCanBeSent();
        } catch (\Magento\Framework\Exception\InputException $e) {
            throw $e;
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Cancel the invitation
     *
     * @return $this
     */
    public function cancel()
    {
        if ($this->canBeCanceled()) {
            $this->setStatus(Invitation\Status::STATUS_CANCELED)->save();
        }
        return $this;
    }

    /**
     * Accept the invitation
     *
     * @param int|string $websiteId
     * @param int $referralId
     * @return $this
     */
    public function accept($websiteId, $referralId)
    {
        $this->makeSureCanBeAccepted($websiteId);
        $this->setReferralId(
            $referralId
        )->setStatus(
            Invitation\Status::STATUS_ACCEPTED
        )->setSignupDate(
            $this->dateTime->formatDate(time())
        )->save();
        $inviterId = $this->getCustomerId();
        if ($inviterId) {
            $this->getResource()->trackReferral($inviterId, $referralId);
        }
        return $this;
    }

    /**
     * Check whether invitation can be accepted
     *
     * @param int $websiteId
     * @return bool
     */
    public function canBeAccepted($websiteId = null)
    {
        try {
            $this->makeSureCanBeAccepted($websiteId);
            return true;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
        }
        return false;
    }

    /**
     * Validating invitation's parameters
     *
     * Returns true or array of errors
     *
     * @return string[]|bool
     */
    public function validate()
    {
        $errors = [];

        if (!\Zend_Validate::is($this->getEmail(), 'EmailAddress')) {
            $errors[] = __('Please correct the invitation email.');
        }

        if (!empty($errors)) {
            return $errors;
        }

        return true;
    }
}
