<?php
/**
 * Call actions after configuration is saved
 */
class Magentothem_Themeoptions_Model_Observer
{
	private $dirPath;
    private $filePath;
    private $dir_store;
	
	private function getConfig($cf,$store_cf){
		if(Mage::getStoreConfig('themeoptions/themeoptions_config/'.$cf,$store_cf))
			return Mage::getStoreConfig('themeoptions/themeoptions_config/'.$cf,$store_cf);
	}
	
	private function setLocation($store_cf) {
		if(Mage::getStoreConfig('design/package/name',$store_cf))
			$this->dir_store = Mage::getStoreConfig('design/package/name',$store_cf).'/';
		else
			$this->dir_store = Mage::getStoreConfig('design/package/name').'/';
		if(Mage::getStoreConfig('design/theme/default',$store_cf))
			$this->dir_store .= Mage::getStoreConfig('design/theme/default',$store_cf);
		else
			$this->dir_store .= 'default';
			
        $this->dirPath = Mage::getBaseDir('skin') . '/frontend/'.$this->dir_store.'/css/';
        $this->filePath = $this->dirPath . 'skin.css';
    }
	
	/**
     * After any system config is saved
     */
	public function cssgenerate()
	{
		$section = Mage::app()->getRequest()->getParam('section');
		if ($section == 'themeoptions')
		{
			$store_ids = array();
			if(Mage::app()->getRequest()->getParam('store') && Mage::app()->getRequest()->getParam('website'))
			{
				$store_ids[] = Mage::getModel( "core/store" )->load(Mage::app()->getRequest()->getParam('store'))->getStore_id();
			}
			elseif(Mage::app()->getRequest()->getParam('website'))
			{
				$store_ids = Mage::getModel('core/website')->load(Mage::app()->getRequest()->getParam('website'))->getstoreIds();
			}else{
				foreach (Mage::app()->getWebsites() as $website){
					foreach ($website->getGroups() as $group){
						$stores = $group->getStores();
						foreach ($stores as $store) {
							$store_ids[] = $store->getId();
						}
					}
				}
			}
			
			foreach($store_ids as $store_id)
			{
				$this->setLocation($store_id);
				
				if(!$this->getConfig('reset_css',$store_id))
				{ 
					
					if($this->getConfig('font_content_main',$store_id))
					$css .= 'body, .footer-static  p, .ma-relatedslider-container .product-name a{font-family:'.str_replace("+"," ",$this->getConfig('font_content',$store_id)).';
						font-weight:'.$this->getConfig('font_contentweight',$store_id).'}';

					if($this->getConfig('font_price_main',$store_id))
					$css .= '.price, .price-box .price, #shopping-cart-table tbody td .price, .timer-grid .box-time-date span{font-family:'.str_replace("+"," ",$this->getConfig('font_price',$store_id)).';
						font-weight:'.$this->getConfig('font_priceweight',$store_id).'}';
						
					// background_color	
					$css .= $this->getConfig('color_tag',$store_id).'{background-color:'.$this->getConfig('color',$store_id).'}';
					
					// background_color_hover
					$css .= $this->getConfig('color_hover_tag',$store_id).'{background-color:'.$this->getConfig('color_hover',$store_id).'}'; 
					
					// text_color
					$css .= $this->getConfig('text_color_tag',$store_id).'{color:'.$this->getConfig('text_color',$store_id).'}';
					
					// text_color_hover
					$css .= $this->getConfig('text_color_hover_tag',$store_id).'{color:'.$this->getConfig('text_color_hover',$store_id).'}';
					
					// border
					$css .= $this->getConfig('border_tag',$store_id).'{border-color:'.$this->getConfig('border',$store_id).'}';
					
					// border_hover
					$css .= $this->getConfig('border_hover_tag',$store_id).'{border-color:'.$this->getConfig('border_hover',$store_id).'}';
					
					$image_bg = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN).'frontend/'.$this->dir_store.'/images/pattern/'.$this->getConfig('bg_pattern',$store_id).'.png';
					$css .= 'body{background-color:'.$this->getConfig('bg_color',$store_id);
					
					if($this->getConfig('bg_pattern',$store_id ))
						$css .=';background-image:url("'.$image_bg.'")';
						$css .='}'; 
					
					
				}else{
					$css = '';
				}
				
				try{
					$fh = new Varien_Io_File(); 
					$fh->setAllowCreateFolders(true); 
					$fh->open(array('path' => $this->dirPath));
					$fh->streamOpen($this->filePath, 'w+'); 
					$fh->streamLock(true); 
					$fh->streamWrite($css); 
					$fh->streamUnlock(); 
					$fh->streamClose(); 
				}
				catch (Exception $e) {
					Mage::getSingleton('adminhtml/session')->addError(Mage::helper('themeoptions')->__('Failed creation custom css rules. '.$e->getMessage()));
				}
			}
		}
	}
}
