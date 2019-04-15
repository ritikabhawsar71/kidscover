<?php
class Magentothem_Producttabs_Block_Sale extends Mage_Core_Block_Template
{
	public function getProducts(){
		$_rootcatID = Mage::app()->getStore()->getRootCategoryId();
		$todayDate  = Mage::app()->getLocale()->date()->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
		$collection = Mage::getResourceModel('catalog/product_collection')
			->joinField('category_id','catalog/category_product','category_id','product_id=entity_id',null,'left')
			->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
			->addMinimalPrice()
			->addUrlRewrite()
			->addTaxPercents()			
			->addStoreFilter()
			->addAttributeToFilter('category_id', array('in' => $_rootcatID))
			->addAttributeToFilter('special_from_date', array('date'=>true, 'to'=> $todayDate))
			->addAttributeToFilter(array(array('attribute'=>'special_to_date', 'date'=>true, 'from'=>$todayDate), array('attribute'=>'special_to_date', 'is' => new Zend_Db_Expr('null'))),'','left');
			
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
		$collection->setPageSize(Mage::helper('producttabs')->getProductCfg('product_number'));
        // get Sale off
        foreach ($collection as $key => $product) {
            if($product->getSpecialPrice() == '') $collection->removeItemByKey($key); // remove product not set SpecialPrice
            if($product->getSpecialPrice() && $product->getSpecialPrice() >= $product->getPrice())
            {
               $collection->removeItemByKey($key); // remove product price increase
            }
        }
        return $collection;
    }
}