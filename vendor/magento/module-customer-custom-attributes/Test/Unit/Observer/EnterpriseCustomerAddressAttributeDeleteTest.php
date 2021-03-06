<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Test\Unit\Observer;

class EnterpriseCustomerAddressAttributeDeleteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\CustomerCustomAttributes\Observer\EnterpriseCustomerAddressAttributeDelete
     */
    protected $observer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteAddressFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderAddressFactory;

    public function setUp()
    {
        $this->quoteAddressFactory = $this->getMockBuilder(
            'Magento\CustomerCustomAttributes\Model\Sales\Quote\AddressFactory'
        )->disableOriginalConstructor()->setMethods(['create'])->getMock();

        $this->orderAddressFactory = $this->getMockBuilder(
            'Magento\CustomerCustomAttributes\Model\Sales\Order\AddressFactory'
        )->disableOriginalConstructor()->setMethods(['create'])->getMock();

        $this->observer = new \Magento\CustomerCustomAttributes\Observer\EnterpriseCustomerAddressAttributeDelete(
            $this->orderAddressFactory,
            $this->quoteAddressFactory
        );
    }

    public function testEnterpriseCustomerAddressAttributeDelete()
    {
        $observer = $this->getMockBuilder('Magento\Framework\Event\Observer')
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder('Magento\Framework\Event')
            ->setMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel = $this->getMockBuilder('Magento\Customer\Model\Attribute')
            ->setMethods(['__wakeup', 'isObjectNew'])
            ->disableOriginalConstructor()
            ->getMock();

        $orderAddress = $this->getMockBuilder('Magento\CustomerCustomAttributes\Model\Sales\Order\Address')
            ->disableOriginalConstructor()
            ->getMock();

        $quoteAddress = $this->getMockBuilder('Magento\CustomerCustomAttributes\Model\Sales\Quote\Address')
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel->expects($this->once())->method('isObjectNew')->will($this->returnValue(false));
        $observer->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $event->expects($this->once())->method('getAttribute')->will($this->returnValue($dataModel));
        $quoteAddress->expects($this->once())->method('deleteAttribute')->with($dataModel)->will($this->returnSelf());
        $this->quoteAddressFactory->expects($this->once())->method('create')->will($this->returnValue($quoteAddress));
        $orderAddress->expects($this->once())->method('deleteAttribute')->with($dataModel)->will($this->returnSelf());
        $this->orderAddressFactory->expects($this->once())->method('create')->will($this->returnValue($orderAddress));
        /** @var \Magento\Framework\Event\Observer $observer */

        $this->assertInstanceOf(
            'Magento\CustomerCustomAttributes\Observer\EnterpriseCustomerAddressAttributeDelete',
            $this->observer->execute($observer)
        );
    }
}
