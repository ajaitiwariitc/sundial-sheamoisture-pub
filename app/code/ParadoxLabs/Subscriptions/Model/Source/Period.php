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

namespace ParadoxLabs\Subscriptions\Model\Source;

/**
 * Period Class
 */
class Period extends \Magento\Catalog\Model\Product\Attribute\Source\Status
{
    const PERIOD_DAY = 'day';
    const PERIOD_WEEK = 'week';
    const PERIOD_MONTH = 'month';
    const PERIOD_YEAR = 'year';

    /**
     * @var array Possible period values
     */
    protected static $allowedPeriods = [
        self::PERIOD_DAY    => 'Day',
        self::PERIOD_WEEK   => 'Week',
        self::PERIOD_MONTH  => 'Month',
        self::PERIOD_YEAR   => 'Year',
    ];

    /**
     * @var array Possible period values (plural)
     */
    protected static $allowedPeriodsPlural = [
        self::PERIOD_DAY    => 'Days',
        self::PERIOD_WEEK   => 'Weeks',
        self::PERIOD_MONTH  => 'Months',
        self::PERIOD_YEAR   => 'Years',
    ];

    /**
     * Get possible period values.
     *
     * @return array
     */
    public static function getOptionArray()
    {
        return static::$allowedPeriods;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $opts = [];

        foreach (static::getOptionArray() as $key => $value) {
            $opts[] = [
                'label' => $value,
                'value' => $key,
            ];
        }

        return $opts;
    }

    /**
     * Retrieve option array with empty value
     *
     * @return string[]
     */
    public function getAllOptions()
    {
        return $this->toOptionArray();
    }

    /**
     * Retrieve option text by option value
     *
     * @param string $optionId
     * @return string
     */
    public function getOptionText($optionId)
    {
        $options = static::getOptionArray();

        return isset($options[$optionId]) ? $options[$optionId] : null;
    }

    /**
     * Get possible period values.
     *
     * @return array
     */
    public static function getOptionArrayPlural()
    {
        return static::$allowedPeriodsPlural;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArrayPlural()
    {
        $opts = [];

        foreach (static::getOptionArrayPlural() as $key => $value) {
            $opts[] = [
                'label' => $value,
                'value' => $key,
            ];
        }

        return $opts;
    }

    /**
     * Retrieve option array with empty value
     *
     * @return string[]
     */
    public function getAllOptionsPlural()
    {
        return $this->toOptionArrayPlural();
    }

    /**
     * Retrieve option text by option value
     *
     * @param string $optionId
     * @return string
     */
    public function getOptionTextPlural($optionId)
    {
        $options = static::getOptionArrayPlural();

        return isset($options[$optionId]) ? $options[$optionId] : null;
    }

    /**
     * Check whether the given period is one of the allowed values.
     *
     * @param string $period
     * @return bool
     */
    public function isAllowedPeriod($period)
    {
        if (in_array($period, array_keys(static::getOptionArray())) !== false) {
            return true;
        }

        return false;
    }
}
