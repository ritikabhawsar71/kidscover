<?php

class Magentothem_Producttabs_Model_System_Config_Type
{
    public function toOptionArray()
    {
        return array(
			array('value' => 'new', 'label'=>Mage::helper('adminhtml')->__('New')), 
            array('value' => 'featured', 'label'=>Mage::helper('adminhtml')->__('Featured')),
			array('value' => 'bestseller', 'label'=>Mage::helper('adminhtml')->__('Bestseller')),
            array('value' => 'mostviewed', 'label'=>Mage::helper('adminhtml')->__('Most Viewed')), 
            array('value' => 'random', 'label'=>Mage::helper('adminhtml')->__('Random')),
            array('value' => 'sale', 'label'=>Mage::helper('adminhtml')->__('Sale')),
        );
    }
}