<?php

// Used for testing purposes
class Notive_Ewarehousing_Adminhtml_EwarehousingbackendController extends Mage_Adminhtml_Controller_Action {

    public function indexAction()
    {
        $cronModel = Mage::getModel('notive_ewarehousing/cron');
//        $cronModel->runStock();
//        $cronModel->runOrders();

        die('Done');
    }

    public function runStock()
    {
        if (Mage::getStoreConfig('Notive_Ewarehousing/stock_sync/enabled', Mage::app()->getStore()) === '1') {
            $this->checkStock();
        }
    }

    public function runOrders()
    {
        if (Mage::getStoreConfig('Notive_Ewarehousing/order_status/enabled', Mage::app()->getStore()) === '1') {
            $this->checkOrderStatus();
        }
    }

    private function createOrder()
    {
        $order = Mage::getModel('sales/order')
            ->loadByIncrementId('100000030');

        $helper_webservice = Mage::getSingleton('Notive_Ewarehousing_Helper_Webservice');

        echo '<pre>';
        var_dump($helper_webservice->createOrder($order));
        echo '<pre>';
        die('done');
    }

    private function checkStock()
    {
        // Get the helpers
        $helper_webservice = Mage::getSingleton('Notive_Ewarehousing_Helper_Webservice');
        $stock = $helper_webservice->getStock();

        if ($stock != false && count($stock) > 0) {
            foreach ($stock as $stockProduct) {
                $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $stockProduct['article_code']);

                if ($product != false) {
                    $productId = $product->getId();
                    $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
                    $stockItem->setData('qty', $stockProduct['salable_stock']);

                    try {
                        $stockItem->save();
                    } catch (Exception $e) {
                        // Do nothing
                    }
                }
            }
        }

        $configModel = Mage::getModel('core/config');
        $configModel->saveConfig('Notive_Ewarehousing/stock_sync/last_sync_date', date('Y-m-d'));
    }



    private function checkOrderStatus()
    {
        // Get the helpers
        $helper_webservice = Mage::getSingleton('Notive_Ewarehousing_Helper_Webservice');
        $helper_data = Mage::getSingleton('Notive_Ewarehousing_Helper_Data');

        // Load the orders
        $orders = $helper_data->getSentOrders();
        $tracking_orders = $helper_webservice->getOrderTracking($orders);
        foreach ($orders as $order) {
            $tracking_order = $tracking_orders[$order->getRealOrderId()];
            if (!is_null($tracking_order) && $tracking_order['sent'] == true) {
                try {
                    $itemQty =  $order->getItemsCollection()->count();
                    $shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($itemQty);
                    $shipment = new Mage_Sales_Model_Order_Shipment_Api();
                    $shipmentId = $shipment->create($order->getRealOrderId());
                    $shipment_model = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentId);

                    foreach ($tracking_order['labels'] as $label) {
                        $carrier_code = 'custom';
                        $carriers = $this->getCarriers();
                        if (isset($carriers[$label['shipper']])) {
                            $carrier_code = $carriers[$label['shipper']];
                        }

                        $track_model = Mage::getModel('sales/order_shipment_track')
                            ->setShipment($shipment_model)
                            ->setData('title', $label['shipper'])
                            ->setData('number', $label['tracking_code'])
                            ->setData('carrier_code', $carrier_code)
                            ->setData('order_id', $shipment_model->getData('order_id'))
                            ->save();
                    }
                    $shipment_model->sendEmail();
                    $shipment_model->setEmailSent(true);
                    $shipment_model->save();
                } catch (Exception $e) {
                    $helper_webservice->writeLog('_checkOrderStatus', $e->getMessage() . ' - ' . ($order->canShip() ? 'Can ship' : 'Can\'t ship'), $order->getRealOrderId());
                }
            }
        }
    }

    public function getCarriers()
    {
        $carriers_config = Mage::getModel('shipping/config');
        $return = array();

        if ($carriers_config !== false) {
            $active_carriers = $carriers_config->getActiveCarriers();
            foreach ($active_carriers as $carrier) {
                $carrierCode = $carrier->getCarrierCode();
                $carrierName = Mage::getStoreConfig('carriers/' . $carrierCode . '/title');
                if (empty($carrierName)) {
                    $carrierName = Mage::getStoreConfig('customtrackers/' . $carrierCode . '/title');
                }

                $return[$carrierName] = $carrierCode;
            }
        }
        return $return;
    }
}
