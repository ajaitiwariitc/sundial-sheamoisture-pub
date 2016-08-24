<?php
namespace Magento\Quote\Api\Data;

/**
 * Extension class for @see \Magento\Quote\Api\Data\TotalsInterface
 */
class TotalsExtension extends \Magento\Framework\Api\AbstractSimpleObject implements \Magento\Quote\Api\Data\TotalsExtensionInterface
{
    /**
     * @return float|null
     */
    public function getRewardPointsBalance()
    {
        return $this->_get('reward_points_balance');
    }

    /**
     * @param float $rewardPointsBalance
     * @return $this
     */
    public function setRewardPointsBalance($rewardPointsBalance)
    {
        $this->setData('reward_points_balance', $rewardPointsBalance);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getRewardCurrencyAmount()
    {
        return $this->_get('reward_currency_amount');
    }

    /**
     * @param float $rewardCurrencyAmount
     * @return $this
     */
    public function setRewardCurrencyAmount($rewardCurrencyAmount)
    {
        $this->setData('reward_currency_amount', $rewardCurrencyAmount);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getBaseRewardCurrencyAmount()
    {
        return $this->_get('base_reward_currency_amount');
    }

    /**
     * @param float $baseRewardCurrencyAmount
     * @return $this
     */
    public function setBaseRewardCurrencyAmount($baseRewardCurrencyAmount)
    {
        $this->setData('base_reward_currency_amount', $baseRewardCurrencyAmount);
        return $this;
    }
}
