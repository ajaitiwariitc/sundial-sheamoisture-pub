<?php
namespace Magento\Quote\Api\Data;

/**
 * ExtensionInterface class for @see \Magento\Quote\Api\Data\TotalsInterface
 */
interface TotalsExtensionInterface extends \Magento\Framework\Api\ExtensionAttributesInterface
{
    /**
     * @return float|null
     */
    public function getRewardPointsBalance();

    /**
     * @param float $rewardPointsBalance
     * @return $this
     */
    public function setRewardPointsBalance($rewardPointsBalance);

    /**
     * @return float|null
     */
    public function getRewardCurrencyAmount();

    /**
     * @param float $rewardCurrencyAmount
     * @return $this
     */
    public function setRewardCurrencyAmount($rewardCurrencyAmount);

    /**
     * @return float|null
     */
    public function getBaseRewardCurrencyAmount();

    /**
     * @param float $baseRewardCurrencyAmount
     * @return $this
     */
    public function setBaseRewardCurrencyAmount($baseRewardCurrencyAmount);
}
