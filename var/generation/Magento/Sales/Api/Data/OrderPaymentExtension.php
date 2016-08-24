<?php
namespace Magento\Sales\Api\Data;

/**
 * Extension class for @see \Magento\Sales\Api\Data\OrderPaymentInterface
 */
class OrderPaymentExtension extends \Magento\Framework\Api\AbstractSimpleObject implements \Magento\Sales\Api\Data\OrderPaymentExtensionInterface
{
    /**
     * @return string|null
     */
    public function getTokenbaseId()
    {
        return $this->_get('tokenbase_id');
    }

    /**
     * @param string $tokenbaseId
     * @return $this
     */
    public function setTokenbaseId($tokenbaseId)
    {
        $this->setData('tokenbase_id', $tokenbaseId);
        return $this;
    }
}
