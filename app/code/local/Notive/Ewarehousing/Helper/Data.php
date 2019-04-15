<?php

class Notive_Ewarehousing_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Get the orders that were sent to eWarehousing
     *
     * @return Mage_Sales_Model_Resource_Order_Collection
     */
    public function getSentOrders()
    {
        // Get all orders that are processing
        $all_orders = Mage::getModel('sales/order')
                    ->getCollection()
                    ->addFieldToFilter('state', 'processing')
        ; // $all_orders

        // Filter the orders that were never sent to eWarehousing
        $sent_order_ids = array();
        foreach ($all_orders as &$order) {
            $status_histories = Mage::getResourceModel('sales/order_status_history_collection')
                ->setOrderFilter($order)
                ->setOrder('created_at', 'desc')
                ->setOrder('entity_id', 'desc')
                ->addFieldToFilter('status', Notive_Ewarehousing_Model_Observers_Order::ORDER_STATUS_CODE_OK)
            ; // $status_histories

            // The order was sent to eWarehousing
            if (count($status_histories) > 0) {
                $sent_order_ids[] = $order->getId();
            }
        }

        // Load the orders that were sent to eWarehousing
        $orders = Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('entity_id', array('in'=>$sent_order_ids))
        ; // $orders

        return $orders;
    }
}
