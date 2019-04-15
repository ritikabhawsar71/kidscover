<?php
class Magentothem_Producttabs_Block_Featured extends Mage_Core_Block_Template
{
	public function getProducts(){
		$_rootcatID = Mage::app()->getStore()->getRootCategoryId();
		$collection = Mage::getResourceModel('catalog/product_collection')
			->joinField('category_id','catalog/category_product','category_id','product_id=entity_id',null,'left')
			->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
			->addMinimalPrice()
			->addUrlRewrite()
			->addTaxPercents()			
			->addStoreFilter()
			->addAttributeToFilter('category_id', array('in' => $_rootcatID))
			//->addAttributeToFilter("featured", 1);		
			->addFieldToFilter(array(
			array('attribute'=>'featured','eq'=>'1'),
			));

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
        $collection->setPageSize(Mage::helper('producttabs')->getProductCfg('product_number'));
        return $collection; 
    }
}