<?php

class Notive_Ewarehousing_Model_Observers_Order extends Notive_Ewarehousing_Model_Observers_Order_Abstract
{
    const ORDER_STATUS_CODE_ERROR = 'notive_ewarehousing_error';
    const ORDER_STATUS_CODE_OK = 'notive_ewarehousing_sent';

    const SAVED_MARK = 'notive_ewarehousing_order_saved';

    private $enabled;
    private $status_send;

    public function _construct()
    {
        parent::_construct();

        // Load config values
        $this->enabled = Mage::getStoreConfig('Notive_Ewarehousing/order/enabled', Mage::app()->getStore()) === '1';
        $this->status_send = explode(',', Mage::getStoreConfig('Notive_Ewarehousing/order/status_send', Mage::app()->getStore()));
    }

    /**
     * Process order
     * @param Varien_Event_Observer|Mage_Sales_Model_Order $event
     * @param bool $manual this is manual call, not from event handler
     */
    public function sales_order_save_after($event, $manual = false)
    {
        $helper_webservice = Mage::getSingleton('Notive_Ewarehousing_Helper_Webservice');

        // Check if this module is enabled
        if (!$manual && !$this->enabled) {
            return;
        }

        if (!$manual) {
            // Find the correct order getter
            $get_order = version_compare(Mage::getVersion(), '1.4', '<') === true ? 'getOrder' : 'getDataObject';

            // If we have no event, or no result from the method
            if (!$event || !$event->$get_order()) {
                // Invalid event data
                return;
            }
            // Set the order
            $order = $event->$get_order();
        } else {
            $order = $event;
        }

        // If the order has already been saved
        if ($order->getData(self::SAVED_MARK)) {
            return;
        }

        // If the order is in one of the send statuses
        if ($this->shouldSendOrder($order)) {
            // Mark order to avoid save loop
            $order->setData(self::SAVED_MARK, true);

            // If successfully sent to eWarehousing
            $result = $helper_webservice->createOrder($order);
            if ($result !== false && !is_null($result) && isset($result['success']) && $result['success']) {
                $this->setStateSent($order);
            } else {
                $msg = '';
                if ($result !== false && !is_null($result) && isset($result['error'])) {
                    $msg = $result['error'];
                }
                $this->setStateNotSent($order, $msg);
            }
        }
    }

    /**
     * Should we send the order to WICS
     * @param  Mage_Sales_Model_Order $order
     * @return bool
     */
    protected function shouldSendOrder(Mage_Sales_Model_Order $order)
    {
        // If the order has reached a status to send
        if (in_array($order->getStatus(), $this->status_send)) {
            // Check if the order has ever been sent
            $comments = $order->getStatusHistoryCollection(true);
            $should_send = true;
            foreach ($comments as $comment) {
                // If the status has ever been a notive_ewarehousing_*** status
                if (preg_match('/^notive_ewarehousing_sent/Usi', $comment->getStatus())) {
                    $should_send = false;
                }
            }
            if ($should_send !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Mark order as not sent
     *
     * @param Mage_Sales_Model_Order $order
     * @throws Mage_Core_Exception
     */
    public function setStateNotSent(Mage_Sales_Model_Order $order, $msg = '')
    {
        if (!$order->canHold()) {
            Mage::throwException($this->__('Error setting hold status.'));
        }
        $order->setHoldBeforeState($order->getState());
        $order->setHoldBeforeStatus($order->getStatus());
        $order->setState(Mage_Sales_Model_Order::STATE_HOLDED, self::ORDER_STATUS_CODE_ERROR, 'Order can\'t be sent to eWarehousing' . ($msg ? ".<br/>Error: {$msg}" : '.'))->save();
    }

    /**
     * Mark order as sent
     *
     * @param Mage_Sales_Model_Order $order
     * @throws Mage_Core_Exception
     */
    public function setStateSent(Mage_Sales_Model_Order $order)
    {
        if ($order->getHoldBeforeState() && !$order->canUnhold()) {
            Mage::throwException($this->__('Error removing hold status.'));
        }
        $state = $order->getHoldBeforeState() ? $order->getHoldBeforeState() : $order->getState();
        $order->setState($state, self::ORDER_STATUS_CODE_OK, 'Order was sent to eWarehousing.')->save();
        $order->setHoldBeforeState(null);
        $order->setHoldBeforeStatus(null);
    }
}
