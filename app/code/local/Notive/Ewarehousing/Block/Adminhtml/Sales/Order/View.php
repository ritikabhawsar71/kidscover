<?php

class Notive_Ewarehousing_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Sales_Order_View
{
    public function __construct()
    {
        parent::__construct();

        $order = $this->getOrder();
        $send_statusses = explode(',', Mage::getStoreConfig('Notive_Ewarehousing/order/status_send', Mage::app()->getStore()));

        if (in_array($order->getStatus(), $send_statusses)) {
            $message = Mage::helper('sales')->__('Are you sure?');
            $this->_addButton('send_to_ewarehousing', array(
                'label'     => Mage::helper('Sales')->__('Send to eWarehousing'),
                'onclick'   => "confirmSetLocation('{$message}', '{$this->getUrl('ewarehousing/sales_order', array('order_id'=>1))}')",
                'class'     => 'go'
            ), 0, 100, 'header', 'header');
        }
    }
}
