<?php
class Magentothem_Producttabs_Block_Random extends Mage_Core_Block_Template
{
	public function getProducts() {
		$_rootcatID = Mage::app()->getStore()->getRootCategoryId();
		$collection = Mage::getResourceModel('catalog/product_collection');
		Mage::getModel('catalog/layer')->prepareProductCollection($collection);
		$collection->getSelect()->order('rand()');
		$collection->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
			->joinField('category_id','catalog/category_product','category_id','product_id=entity_id',null,'left')
			->addMinimalPrice()
			->addUrlRewrite()
			->addTaxPercents()
			->addStoreFilter()
			->addAttributeToFilter('category_id', array('in' => $_rootcatID));	
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
        $collection->setPageSize(Mage::helper('producttabs')->getProductCfg('product_number'));
        return $collection;
    }
}