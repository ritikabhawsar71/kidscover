<?php

class Notive_Ewarehousing_Block_Adminhtml_Ewarehousingbackend extends Mage_Adminhtml_Block_Template
{

    public function getConfigValue($name)
    {
        return Mage::getStoreConfig('notive/ewarehousing/'.$name, Mage::app()->getStore());
    }
}
