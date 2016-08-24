<?php
namespace Magento\Sales\Api\Data;

/**
 * ExtensionInterface class for @see \Magento\Sales\Api\Data\OrderPaymentInterface
 */
interface OrderPaymentExtensionInterface extends \Magento\Framework\Api\ExtensionAttributesInterface
{
    /**
     * @return string|null
     */
    public function getTokenbaseId();

    /**
     * @param string $tokenbaseId
     * @return $this
     */
    public function setTokenbaseId($tokenbaseId);
}
