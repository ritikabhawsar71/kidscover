<?php
    class Magentothem_Testimonial_Block_Sidebar extends Mage_Core_Block_Template {
		
		public function __construct() {			
			parent::__construct();			
			$this->setTemplate('magentothem/testimonial/sidebar/slider.phtml');		
		}
		
		protected function _prepareLayout()
		{		
			return parent::_prepareLayout();
		}
		
        public function getTestimonialsLast(){
			$storeId = Mage::app()->getStore()->getId();
			$limit = Mage::helper('testimonial')->getMaxTestimonialsOnSidebar();
            $collection = Mage::getModel('testimonial/testimonial')->getCollection()
				->addFieldToFilter('stores',
						array(
							array('finset'=> array('0')),
							array('finset'=> array($storeId)),
						)
					);
			$collection->setOrder('created_time', 'DESC');
			$collection->addFieldToFilter('status',1);
			$collection->setPageSize($limit);
			return $collection;
		}
		
		public function getContentTestimonialSidebar($_description, $count) {
		   $short_desc = substr($_description, 0, $count);
		   if(strlen($short_desc) == strlen($_description))
				return $_description;
		   if(substr($short_desc, 0, strrpos($short_desc, ' '))!='') {
				$short_desc = substr($short_desc, 0, strrpos($short_desc, ' '));
				$short_desc = $short_desc.'...';
		    }
		   return $short_desc;
		}
		
		public function getConfig($att) 
		{
			$config = Mage::getStoreConfig('testimonial');
			if (isset($config['testimonial_slide'])) {
				$value = $config['testimonial_slide'][$att];
				return $value;
			} else {
				throw new Exception($att.' value not set');
			}
		}
    }
?>