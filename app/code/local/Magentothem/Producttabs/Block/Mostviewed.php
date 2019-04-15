<?php
class Magentothem_Producttabs_Block_Mostviewed extends Mage_Core_Block_Template
{
	public function getProducts(){
		$_rootcatID = Mage::app()->getStore()->getRootCategoryId();
		$storeId    = Mage::app()->getStore()->getId();
		$collection = Mage::getResourceModel('reports/product_collection')
			->joinField('category_id','catalog/category_product','category_id','product_id=entity_id',null,'left')
            ->addAttributeToSelect('*')
			->addMinimalPrice()
			->addUrlRewrite()
			->addTaxPercents()			
            ->addAttributeToSelect(array('name', 'price', 'small_image')) //edit to suit tastes
            ->setStoreId($storeId)
            ->addStoreFilter($storeId)
            ->addViewsCount()
			->addAttributeToFilter('category_id', array('in' => $_rootcatID))
            ;			
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
        $this->setProductCollection($collection);
		$collection->setPageSize(Mage::helper('producttabs')->getProductCfg('product_number'));

		if(Mage::getStoreConfig('catalog/frontend/flat_catalog_product'))
        {
            // fix error mat image vs name while Enable useFlatCatalogProduct
            foreach ($collection as $product) 
            {
                $productId = $product->_data['entity_id'];
                $_product = Mage::getModel('catalog/product')->load($productId); //Product ID
                $product->_data['name']        = $_product->getName();
                $product->_data['thumbnail']   = $_product->getThumbnail();
                $product->_data['small_image'] = $_product->getSmallImage();
            }            
        }
        
        return $collection;
    }
}