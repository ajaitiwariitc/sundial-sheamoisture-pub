<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Magento\MultipleWishlist\Controller\Index;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NotFoundException;
use Magento\MultipleWishlist\Controller\IndexInterface;
use Magento\MultipleWishlist\Model\ItemManager;
use Magento\Wishlist\Controller\WishlistProviderInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Moveitem extends \Magento\MultipleWishlist\Controller\AbstractIndex
{
    /**
     * @var WishlistProviderInterface
     */
    protected $wishlistProvider;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\MultipleWishlist\Model\ItemManager
     */
    protected $itemManager;

    /**
     * @var \Magento\Wishlist\Model\ItemFactory
     */
    protected $itemFactory;

    /**
     * @var \Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory
     */
    protected $wishlistColFactory;

    /**
     * @param Context $context
     * @param WishlistProviderInterface $wishlistProvider
     * @param Session $customerSession
     * @param ItemManager $itemManager
     * @param \Magento\Wishlist\Model\ItemFactory $itemFactory
     * @param \Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory $wishlistColFactory
     */
    public function __construct(
        Context $context,
        WishlistProviderInterface $wishlistProvider,
        Session $customerSession,
        ItemManager $itemManager,
        \Magento\Wishlist\Model\ItemFactory $itemFactory,
        \Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory $wishlistColFactory
    ) {
        $this->wishlistProvider = $wishlistProvider;
        $this->customerSession = $customerSession;
        $this->itemManager = $itemManager;
        $this->itemFactory = $itemFactory;
        $this->wishlistColFactory = $wishlistColFactory;
        parent::__construct($context);
    }

    /**
     * Move wishlist item to given wishlist
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws NotFoundException
     */
    public function execute()
    {
        $wishlist = $this->wishlistProvider->getWishlist();
        if (!$wishlist) {
            throw new NotFoundException(__('Page not found.'));
        }
        $itemId = $this->getRequest()->getParam('item_id');

        if ($itemId) {
            try {
                /** @var \Magento\Wishlist\Model\ResourceModel\Wishlist\Collection $wishlists */
                $wishlists = $this->wishlistColFactory->create();
                $wishlists->filterByCustomerId($this->customerSession->getCustomerId());

                /* @var \Magento\Wishlist\Model\Item $item */
                $item = $this->itemFactory->create();
                $item->loadWithOptions($itemId);

                $productName = $this->_objectManager->get(
                    'Magento\Framework\Escaper'
                )->escapeHtml(
                    $item->getProduct()->getName()
                );
                $wishlistName = $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($wishlist->getName());

                $this->itemManager->move($item, $wishlist, $wishlists, $this->getRequest()->getParam('qty', null));
                $this->messageManager->addSuccess(__('"%1" was moved to %2.', $productName, $wishlistName));
                $this->_objectManager->get('Magento\Wishlist\Helper\Data')->calculate();
            } catch (\InvalidArgumentException $e) {
                $this->messageManager->addError(__('We can\'t find an item with this ID.'));
            } catch (\DomainException $e) {
                if ($e->getCode() == 1) {
                    $this->messageManager->addError(__('"%1" is already present in %2.', $productName, $wishlistName));
                } else {
                    $this->messageManager->addError(__('We cannot move "%1".', $productName));
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('We can\'t move the wish list item.'));
            }
        }
        $wishlist->save();
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRedirectUrl());
        return $resultRedirect;
    }
}
