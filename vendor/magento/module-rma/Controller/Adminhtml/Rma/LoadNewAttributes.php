<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Magento\Rma\Controller\Adminhtml\Rma;

class LoadNewAttributes extends \Magento\Rma\Controller\Adminhtml\Rma
{
    /**
     * Load user-defined attributes for new RMA's item
     *
     * @return void
     */
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');
        $itemId = $this->getRequest()->getParam('item_id');

        /** @var $rma_item \Magento\Rma\Model\Item */
        $rma_item = $this->_objectManager->create('Magento\Rma\Model\Item');
        $this->_coreRegistry->register('current_rma_item', $rma_item);

        $this->_view->loadLayout();
        $form = $this->_view->getLayout()
            ->getBlock('magento_rma_edit_item')
            ->setProductId(intval($productId))
            ->setHtmlPrefixId(intval($itemId))
            ->initForm();
        if ($form->hasNewAttributes()) {
            $response = $form->toHtml();

            if (is_array($response)) {
                $this->getResponse()->representJson(
                    $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($response)
                );
            } else {
                $this->getResponse()->setBody($response);
            }
        }
    }
}
