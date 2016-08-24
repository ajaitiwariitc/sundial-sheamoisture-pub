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

namespace ParadoxLabs\Subscriptions\Block\Adminhtml\Subscription\View\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

/**
 * Payment tab
 */
class Payment extends Generic implements TabInterface
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $tokenbaseHelper;

    /**
     * @var \Magento\Backend\Model\Url
     */
    protected $url;

    /**
     * @var \Magento\Customer\Model\Address\Mapper
     */
    protected $addressMapper;

    /**
     * @var \Magento\Customer\Model\Address\Config
     */
    protected $addressConfig;

    /**
     * Payment tab constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \ParadoxLabs\TokenBase\Helper\Data $tokenbaseHelper
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param \Magento\Backend\Model\Url $url
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \ParadoxLabs\TokenBase\Helper\Data $tokenbaseHelper,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Magento\Backend\Model\Url $url,
        array $data
    ) {
        parent::__construct($context, $registry, $formFactory, $data);

        $this->tokenbaseHelper = $tokenbaseHelper;
        $this->url = $url;
        $this->addressMapper = $addressMapper;
        $this->addressConfig = $addressConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Payment');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Payment');
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \ParadoxLabs\Subscriptions\Model\Subscription $subscription */
        $subscription = $this->_coreRegistry->registry('current_subscription');

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $subscription->getQuote();

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('payment_');

        $fieldset = $form->addFieldset('fieldset_payment', ['legend' => __('Payment Information')]);

        $activeCardId = $quote->getPayment()->getData('tokenbase_id');
        $activeCard = null;

        $cardOptions = [];
        /** @var \ParadoxLabs\TokenBase\Model\Card $card */
        foreach ($this->tokenbaseHelper->getActiveCustomerCardsByMethod() as $card) {
            $card = $card->getTypeInstance();

            $cardOptions[$card->getHash()] = $card->getLabel();

            if ($card->getId() == $activeCardId) {
                $activeCard = $card;
            }
        }

        $fieldset->addField(
            'payment_note',
            'note',
            [
                'name'  => 'payment_note',
                'label' => __(''),
                'text'  => __(
                    'This payment record will be used for future payments. <b>Any changes will take effect on the next '
                    . 'billing.</b><br />To add or modify payment data, please go to the '
                    . '<a href="%1" target="_blank">customer profile</a>.',
                    $this->url->getUrl('customer/index/edit', ['id' => $subscription->getCustomerId()])
                )
            ]
        );

        $fieldset->addField(
            'tokenbase_id',
            'select',
            [
                'name'    => 'tokenbase_id',
                'label'   => __('Payment Account'),
                'title'   => __('Payment Account'),
                'options' => $cardOptions,
                'required' => true,
            ]
        );

        $fieldset->addField(
            'billing_address',
            'note',
            [
                'name'  => 'billing_address',
                'label' => __('Billing Address'),
                'text'  => $activeCard ? $this->getFormattedAddress($activeCard->getAddressObject()) : '',
                'note'  => __(
                    'Address corresponding to %1. Will be updated automatically based on the chosen account.',
                    $activeCard->getLabel()
                )
            ]
        );

        if ($activeCard) {
            $form->setValues([
                'tokenbase_id' => $activeCard->getHash(),
            ]);
        }

        $this->setForm($form);

        $this->_eventManager->dispatch('adminhtml_subscription_view_tab_payment_prepare_form', ['form' => $form]);

        return parent::_prepareForm();
    }

    /**
     * Get HTML-formatted card address. This is silly, but it's how the core says to do it.
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @param string $format
     * @return string
     * @see \Magento\Customer\Model\Address\AbstractAddress::format()
     */
    public function getFormattedAddress(\Magento\Customer\Api\Data\AddressInterface $address, $format = 'html')
    {
        /** @var \Magento\Customer\Block\Address\Renderer\RendererInterface $renderer */
        $renderer    = $this->addressConfig->getFormatByCode($format)->getRenderer();
        $addressData = $this->addressMapper->toFlatArray($address);

        return $renderer->renderArray($addressData);
    }
}
