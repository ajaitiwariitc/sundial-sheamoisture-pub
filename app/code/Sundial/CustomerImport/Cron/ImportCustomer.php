<?php
namespace Sundial\CustomerImport\Cron;
class ImportCustomer
{
	protected $storeManager;
    protected $customerFactory;
	protected $addressFactory;
	protected $regionCollectionFactory;
	protected $eavConfig;
	protected $subscriberFactory;
    protected $logger;
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface  $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
		\Magento\Customer\Model\Customer $customer,
		\Magento\Eav\Model\Config $eavConfig,
		\Magento\Customer\Model\AddressFactory $addressFactory,
		\Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
		\Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
		\Psr\Log\LoggerInterface $logger
		
    ) {
        $this->storeManager     = $storeManager;
        $this->customerFactory  = $customerFactory;
		$this->eavConfig = $eavConfig;
		$this->addressFactory  	= $addressFactory;
		$this->regionCollectionFactory = $regionCollectionFactory;
		$this->subscriberFactory = $subscriberFactory;
		$this->logger = $logger;
    }

    public function execute()
    {
		
		$websiteId  = $this->storeManager->getStore()->getWebsiteId();
		$customerdata = $this->getCustomerDataByApi();
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		foreach($customerdata as $customervalue){
			//checking customer already exists or not.
			$customerAlreadyAvailable = $objectManager->create('Magento\Customer\Model\Customer')->setWebsiteId($websiteId)->loadByEmail($customervalue['email']);
			if($customerAlreadyAvailable->hasData()){
				$customer   = $customerAlreadyAvailable;
			}else{
				$customer   = $this->customerFactory->create();
			}
			$customerDataobj = $customer->getDataModel();
			// Preparing data for new customer
			$grourpId = $customervalue['group_id'];//$this->getCustomerGroupMappedId($customervalue['group_id']);			
			$isActive = ($customervalue['is_active'] == true ) ? 1: 0;
			$mailList = ($customervalue['mail_list'] == true ) ? 1: 0;
			$customerDataobj->setWebsiteId($websiteId);			
			$customerDataobj->setEmail($customervalue['email']); 
			$customerDataobj->setCustomAttribute('api_customer_id', $customervalue['api_customer_id']);
			$customerDataobj->setGroupId($grourpId);
			$customer->setIsActive($isActive);//this will be default is 1 we can't change.			
			$customerDataobj->setFirstname($customervalue['billing_address']['firstname']);
			$customerDataobj->setLastname($customervalue['billing_address']['lastname']);
			$customer->setPassword($customervalue['password']);
			// Save data
			try{
				echo "<pre>";
				print_r($customer->getData());
echo "testing";
				exit;
				 $customer->updateData($customerDataobj);
                 $customer->save();
				 if($mailList){
				  $this->subscriberFactory->create()->subscribeCustomerById($customer->getId());
				 }
            }catch (Exception $e) {
				 $this->logger->info($customervalue['email'].'=='.$customervalue['api_customer_id']);
                 Zend_Debug::dump($e->getMessage());
            }
			$billingData = $customervalue['billing_address'];
			$shippingData = $customervalue['shipping_address'];
			if($customervalue['billing_same_as_shipping']){
				$this->saveBillingSameAsShipping($billingData,$customer->getId());
			}else{
				$this->saveBillingAddress($billingData,$customer->getId());
				$this->saveShippingAddress($shippingData,$customer->getId());
			}
			
		}		
    }
	
	public function getCustomerDataFromAPI(){
		$ServiceURL = 'http://test.i3lsundial.com/migration/customer.json';
		if(isset($ServiceURL) && $ServiceURL != ''){
			$headers = array(
				'Accept: application/json',
				'Content-Type:  application/json',
			);

			$curl = curl_init($ServiceURL);
			curl_setopt($curl, CURLOPT_URL, $ServiceURL);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			//curl_setopt($curl, CURLOPT_COOKIEJAR, $cookiefile);
			//curl_setopt($curl, CURLOPT_COOKIEFILE, $cookiefile);
			
			$JSONResponse = curl_exec($curl);
			$StatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			
			if( $JSONResponse == false ) {
				echo 'Curl error: ' . curl_error($curl);exit;
			} else {
				if($StatusCode == 200){
								$data = json_decode($JSONResponse,true);
				}              
			}
		}else {
			echo 'Error: URL is empty.' ;
		}

		return $data;

	}              

	
	public function getCustomerGroupMappedId($apiCustomerGroupId){		
		$customerGroupMappArray = array(
			'2' => '1',
			'1' => '1',
			'4' => '1',
			'5' => '1',
			'6' => '1',
			'7' => '4',
			'8' => '1',
			'9' => '1',
			'10' => '5',
			'11' => '1'
		);
		
		if (array_key_exists($apiCustomerGroupId,$customerGroupMappArray)) {
			$mappedCustomerGroupId = $customerGroupMappArray[$apiCustomerGroupId];
		} else {
			$mappedCustomerGroupId = 1;
		}
		return $mappedCustomerGroupId;            

	}
	/* Get the 3rd cart data as array.	
	*/
	public function getCustomerDataByApi(){
                                
		$customersData = $this->getCustomerDataFromAPI();
		$i = 0;
		foreach($customersData as $item) {
			$password = $this->getPassword($item["Email"]);
			$customersArray[$i]["api_customer_id"] = $item["CustomerID"];
			$customersArray[$i]["email"] = $item["Email"];
			$customersArray[$i]["password"] = $password;
			$customersArray[$i]["is_active"] = $item["Enabled"];
			$customersArray[$i]["mail_list"] = $item["MailList"];//
			$customersArray[$i]["non_taxable"] = $item["NonTaxable"];//
			$customersArray[$i]["comments"] = $item["Comments"]; //
			$customersArray[$i]["shipping_address_type"] = $item["ShippingAddressType"]; //			
			/*if($item["DisableBillingSameAsShipping"] === False){
				$billingSameAsShipping = True ;
			} else {
				$billingSameAsShipping = False ;
			}*/
			$customersArray[$i]["billing_same_as_shipping"] = $item["DisableBillingSameAsShipping"];//$billingSameAsShipping;
			$customersArray[$i]["group_id"] = $this->getCustomerGroupMappedId($item["CustomerGroupID"]);
			
			$customersArray[$i]["billing_address"]["company"] = $item["BillingCompany"];
			$customersArray[$i]["billing_address"]["firstname"] = $item["BillingFirstName"];
			$customersArray[$i]["billing_address"]["lastname"] = $item["BillingLastName"];
			$customersArray[$i]["billing_address"]["street"] = $item["BillingAddress1"].' '.$item["BillingAddress2"];
			$customersArray[$i]["billing_address"]["city"] = $item["BillingCity"];
			$customersArray[$i]["billing_address"]["region"] = $item["BillingState"];
			$customersArray[$i]["billing_address"]["postcode"] = $item["BillingZipCode"];
			$customersArray[$i]["billing_address"]["country_id"] = $item["BillingCountry"];
			$customersArray[$i]["billing_address"]["telephone"] = $item["BillingPhoneNumber"];
			$customersArray[$i]["billing_address"]["billing_tax_id"] = $item["BillingTaxID"]; //
			
			$customersArray[$i]["shipping_address"]["company"] = $item["ShippingCompany"];
			$customersArray[$i]["shipping_address"]["firstname"] = $item["ShippingFirstName"];
			$customersArray[$i]["shipping_address"]["lastname"] = $item["ShippingLastName"];
			$customersArray[$i]["shipping_address"]["street"] = $item["ShippingAddress1"].' '.$item["ShippingAddress2"];
			$customersArray[$i]["shipping_address"]["city"] = $item["ShippingCity"];
			$customersArray[$i]["shipping_address"]["region"] = $item["ShippingState"];
			$customersArray[$i]["shipping_address"]["postcode"] = $item["ShippingZipCode"];
			$customersArray[$i]["shipping_address"]["country_id"] = $item["ShippingCountry"];
			$customersArray[$i]["shipping_address"]["telephone"] = $item["ShippingPhoneNumber"];		
			$i++;
		}
		return $customersArray;              

	}
	/* To save the billing and shipping  address as default
	* @param array addressData
	* @param int customerId
	*/
	public function saveBillingSameAsShipping($addressData,$customerId){
		
			$websiteId  = $this->storeManager->getStore()->getWebsiteId();
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$customerAlreadyAvailable = $objectManager->create('Magento\Customer\Model\Customer')->setWebsiteId($websiteId)->load($customerId);
			if($customerAlreadyAvailable->hasData()){
				$address = $objectManager->create('Magento\Customer\Model\Customer')->getAddressById($customerAlreadyAvailable->getDefaultBilling());
			}else{
				 $address = $this->addressFactory->create();
			}			
            $address->setCustomerId($customerId)
            ->setFirstname($addressData['firstname'])
            ->setLastname($addressData['lastname'])
            ->setCountryId($addressData['country_id'])
            ->setPostcode($addressData['postcode'])
            ->setCity($addressData['city'])
            ->setTelephone($addressData['telephone'])
            ->setCompany($addressData['company'])
            ->setStreet($addressData['street'])
            ->setIsDefaultBilling('1')
			->setIsDefaultShipping('1')
            ->setSaveInAddressBook('1');
			$region = $this->getRegionIdByName($addressData['region'],$addressData['country_id']);
			if ($region->getSize()) {
				$address->setRegionId($region->getFirstItem()->getId())
				->setRegion($region->getFirstItem()->getName());
			}else{
				$address->setRegion($addressData['region']);
			}
            try{
                $address->save();
            }catch (Exception $e) {
                Zend_Debug::dump($e->getMessage());
            }
	}
	/* To save the billing address as default
	* @param array billingData
	* @param int customerId
	*/
	public function saveBillingAddress($billingData,$customerId){
		    $websiteId  = $this->storeManager->getStore()->getWebsiteId();
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$customerAlreadyAvailable = $objectManager->create('Magento\Customer\Model\Customer')->setWebsiteId($websiteId)->load($customerId);
			if($customerAlreadyAvailable->hasData()){
				$address = $objectManager->create('Magento\Customer\Model\Customer')->getAddressById($customerAlreadyAvailable->getDefaultBilling());
			}else{
				 $address = $this->addressFactory->create();
			}		
            $address->setCustomerId($customerId)
            ->setFirstname($billingData['firstname'])
            ->setLastname($billingData['lastname'])
            ->setCountryId($billingData['country_id'])
            ->setPostcode($billingData['postcode'])
            ->setCity($billingData['city'])
            ->setTelephone($billingData['telephone'])
            ->setCompany($billingData['company'])
            ->setStreet($billingData['street'])
            ->setIsDefaultBilling('1')
            ->setSaveInAddressBook('1');
			$region = $this->getRegionIdByName($billingData['region'],$billingData['country_id']);
			if ($region->getSize()) {
				$address->setRegionId($region->getFirstItem()->getId())
				->setRegion($region->getFirstItem()->getName());
			}else{
				$address->setRegion($billingData['region']);
			}
            try{
                $address->save();
            }catch (Exception $e) {
                Zend_Debug::dump($e->getMessage());
            }
	}
	/* To save the shipping address as default
	* @param array shippingData
	* @param int customerId
	*/
	public function saveShippingAddress($shippingData,$customerId){
		    $websiteId  = $this->storeManager->getStore()->getWebsiteId();
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$customerAlreadyAvailable = $objectManager->create('Magento\Customer\Model\Customer')->setWebsiteId($websiteId)->load($customerId);
			if($customerAlreadyAvailable->hasData()){
				$address = $objectManager->create('Magento\Customer\Model\Customer')->getAddressById($customerAlreadyAvailable->getDefaultShipping());
			}else{
				 $address = $this->addressFactory->create();
			}	
            $address->setCustomerId($customerId)
            ->setFirstname($shippingData['firstname'])
            ->setLastname($shippingData['lastname'])
            ->setCountryId($shippingData['country_id'])
            ->setPostcode($shippingData['postcode'])
            ->setCity($shippingData['city'])
            ->setTelephone($shippingData['telephone'])
            ->setCompany($shippingData['company'])
            ->setStreet($shippingData['street'])
            ->setIsDefaultShipping('1')
            ->setSaveInAddressBook('1');
			$region = $this->getRegionIdByName($shippingData['region'],$shippingData['country_id']);
			if ($region->getSize()) {
				$address->setRegionId($region->getFirstItem()->getId())
				->setRegion($region->getFirstItem()->getName());
			}else{
				$address->setRegion($shippingData['region']);
			}
            try{
                $address->save();
            }catch (Exception $e) {
                Zend_Debug::dump($e->getMessage());
            }
	}
	/*get the state id based on the country and state name.
	* @param string region 
	* @param string countryId 
	*/	
	public function getRegionIdByName($region, $countryId)
    {
        $collection = $this->regionCollectionFactory->create()
            ->addRegionCodeOrNameFilter($region)
            ->addCountryFilter($countryId);
		return $collection;
    }
	public function getPassword($email)
	{
			$passwordstring = '';
			$tmp = explode ('@', $email);
			$passwordstring = preg_replace('/[^A-Za-z0-9\-]/', '',  $tmp[0]);
			$password = $passwordstring.'#9341';
			return $password;
	}
}

