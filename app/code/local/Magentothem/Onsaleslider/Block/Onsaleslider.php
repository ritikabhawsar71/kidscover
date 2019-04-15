<?php
class Magentothem_Onsaleslider_Block_Onsaleslider extends Mage_Catalog_Block_Product_Abstract
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getOnsaleslider()     
     { 
        if (!$this->hasData('onsaleslider')) {
            $this->setData('onsaleslider', Mage::registry('onsaleslider'));
        }
        return $this->getData('onsaleslider');
        
    }
	public function getProducts()
    {
		$todayDate  = Mage::app()->getLocale()->date()->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
    	$storeId    = Mage::app()->getStore()->getId();
		$products = Mage::getResourceModel('catalog/product_collection')
			->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
			->addMinimalPrice()
			->addUrlRewrite()
			->addTaxPercents()			
			->addStoreFilter()
			->addAttributeToFilter('special_from_date', array('date'=>true, 'to'=> $todayDate))
			->addAttributeToFilter(array(array('attribute'=>'special_to_date', 'date'=>true, 'from'=>$todayDate), array('attribute'=>'special_to_date', 'is' => new Zend_Db_Expr('null'))),'','left');
			
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($products);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($products);
        $products->setPageSize($this->getConfig('qty'))->setCurPage(1);
        $this->setProductCollection($products);
    }
	public function getConfig($att) 
	{
		$config = Mage::getStoreConfig('onsaleslider');
		if (isset($config['onsaleslider_config']) ) {
			$value = $config['onsaleslider_config'][$att];
			return $value;
		} else {
			throw new Exception($att.' value not set');
		}
	}
	
	function cut_string_onsaleproductslider($string,$number){
		if(strlen($string) <= $number) {
			return $string;
		}
		else {	
			if(strpos($string," ",$number) > $number){
				$new_space = strpos($string," ",$number);
				$new_string = substr($string,0,$new_space)."..";
				return $new_string;
			}
			$new_string = substr($string,0,$number)."..";
			return $new_string;
		}
	}
}