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

namespace ParadoxLabs\Subscriptions\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * UpgradeData Class
 */
class UpgradeData implements \Magento\Framework\Setup\UpgradeDataInterface
{
    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory
     */
    protected $quoteCollectionFactory;

    /**
     * @var \ParadoxLabs\Subscriptions\Helper\Data
     */
    protected $helper;

    /**
     * UpgradeData constructor.
     *
     * @param \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory
     * @param \ParadoxLabs\Subscriptions\Helper\Data $helper
     */
    public function __construct(
        \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory,
        \ParadoxLabs\Subscriptions\Helper\Data $helper
    ) {
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->helper = $helper;
    }

    /**
     * Data upgrade
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        /**
         * Make sure that no subscription quote ever has a updated_at date in the past, or it's at risk of getting
         * pruned. Bye-bye subscription.
         *
         * We bypass the prune check via the updated_at date as such to ensure data persistence even if the module
         * should be temporarily disabled. A plugin doesn't ensure that.
         */

        $quotes = $setup->getTable('quote');
        $subs   = $setup->getTable('paradoxlabs_subscription');
        $setup->run(
            "UPDATE {$quotes} SET updated_at='2038-01-01'
              WHERE entity_id IN (
                SELECT quote_id FROM {$subs} 
              ) AND updated_at<'2038-01-01'"
        );

        $setup->endSetup();
    }
}
