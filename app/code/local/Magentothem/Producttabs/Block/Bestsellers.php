<?php
class Magentothem_Producttabs_Block_Bestsellers extends Mage_Core_Block_Template
{
	public function getProducts(){
		$_rootcatID = Mage::app()->getStore()->getRootCategoryId();
        $collection = Mage::getModel('catalog/product')->getCollection()
		->joinField('category_id','catalog/category_product','category_id','product_id=entity_id',null,'left')
		->addAttributeToFilter('category_id', array('in' => $_rootcatID));
        $collection->addAttributeToSelect('*')->addStoreFilter();
        $orderItems = Mage::getSingleton('core/resource')->getTableName('sales/order_item');
        $orderMain =  Mage::getSingleton('core/resource')->getTableName('sales/order');
        $collection->getSelect()
            ->join(array('items' => $orderItems), "items.product_id = e.entity_id", array('count' => 'SUM(items.qty_ordered)'))
            ->join(array('trus' => $orderMain), "items.order_id = trus.entity_id", array())
            ->group('e.entity_id')
            ->order('count DESC');
		Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
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