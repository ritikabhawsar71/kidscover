<?php

class Notive_Ewarehousing_Helper_Webservice
{
    const WEBSERVICE_URL = 'http://ws.ewarehousing.nl';
    const VERSION_PLUGIN = '2.1.2';

    private $customer_id = '';
    private $context = '';

    /**
     * Login check before Webservice Class is allowed to be used
     */
    public function __construct()
    {
        $customer_id = Mage::getStoreConfig('Notive_Ewarehousing/general/customer_id', Mage::app()->getStore());
        $username = Mage::getStoreConfig('Notive_Ewarehousing/general/username', Mage::app()->getStore());
        $password = Mage::getStoreConfig('Notive_Ewarehousing/general/password', Mage::app()->getStore());

        if ($this->login($username, $password, $customer_id) == false) {
            $this->customer_id = null;
            $this->context = null;
        }
    }

    /**
     * Perform an API call
     * @param  string $endpoint The endpoint for the API call
     * @param  array $data      An associative array containing the params for the call
     * @return array            An associative array with the response
     */
    private function call($endpoint, $data)
    {
        $data['plugin'] = array(
            'plugin_version'    => self::VERSION_PLUGIN,
            'plugin_type'       => 'MAGENTO'
        );

        $curl_call = curl_init();
        curl_setopt($curl_call, CURLOPT_URL, self::WEBSERVICE_URL.$endpoint.'?plugin_version='.self::VERSION_PLUGIN);
        curl_setopt($curl_call, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_call, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_call, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl_call, CURLOPT_POST, true);
        curl_setopt($curl_call, CURLOPT_TIMEOUT, 300);
        curl_setopt($curl_call, CURLOPT_POSTFIELDS, http_build_query($data));

        if ($endpoint !== '/2/auth') {
            curl_setopt($curl_call, CURLOPT_USERPWD, $this->customer_id.':'.$this->context);
        }


        $response = curl_exec($curl_call);
        if ($response === false) {
            return array(
                'success'=>false,
                'error'=>'Curl error: '.curl_error($curl_call)
            );
        }
        return json_decode($response, true);
    }

    /**
     * Access the API
     * @param  string $username     The username
     * @param  string $password     The password
     * @param  int $customer_id     The eWarehousing customer number
     * @return boolean              Whether or not we have succesfully logged in
     */
    private function login($username, $password, $customer_id)
    {
        $returned = $this->call('/2/auth', array(
            'username'=>$username,
            'password'=>md5($password),
            'customer_id'=>$customer_id
        ));

        if (!isset($returned['success']) || $returned['success'] != false) {
            $this->context = $returned['context'];
            $this->customer_id = $customer_id;
            return true;
        }
        return false;
    }

    /**
     * Check wheter or not we are logged in to the API
     * @return boolean Logged in or not
     */
    private function isLoggedIn()
    {
        return !is_null($this->customer_id) && !is_null($this->context);
    }

    public function getStock()
    {
        if ($this->isLoggedIn()) {
            // If it's the first time we want to sync all products
            $last_sync_date = Mage::getStoreConfig('Notive_Ewarehousing/stock_sync/last_sync_date', Mage::app()->getStore());
            if ($last_sync_date == false || $last_sync_date == '') {
                $last_sync_date = '1970-01-01 00:00:00';
            }

            $page = 1;
            $return = array();
            $limit = 1000;
            while (true) {
                $result = $this->call('/2/stock', array('updated_after' => $last_sync_date, 'limit' => $limit, 'page' => $page));
                $return = array_merge($return, $result);
                $page++;
                if (count($result) < $limit) {
                    break;
                }
            }
            return $return;
        }
    }

    /**
     * Create the order in the API
     * @param Mage_Sales_Model_Order $order [description]
     */
    public function createOrder(Mage_Sales_Model_Order $order)
    {
        if ($this->isLoggedIn()) {
            // Get the address (Mage_Sales_Model_Order_Address)
            $addr_billing = $order->getBillingAddress();
            $addr_shipping = $order->getShippingAddress();
            $address = $addr_shipping ? $addr_shipping : $addr_billing;
            list($street, $street_number, $number_extention) = $this->splitAddress($address);

            $date = date('Y-m-d');
            if ($order->getData('preferred_delivery_date')) {
                $date = $order->getData('preferred_delivery_date');
            }

            $data = array(
                'date'=>$date,
                'reference'=>$order->getRealOrderId(),
                'address' => array(
                    'addressed_to'=>$address->getName(),
                    'street'=>$street,
                    'street_number'=>$street_number,
                    'number_extention'=>$number_extention,
                    'zipcode'=>$address->getPostcode(),
                    'city'=>$address->getCity(),
                    'country_code'=>$address->getCountry(),
                    'phone_number'=>$address->getTelephone(),
                    'email'=>$order->getCustomerEmail()
                ),
                'order_lines' => array()
            );

            if ($address->getCompany() != '') {
                $data['address']['addressed_to'] = $address->getCompany();
                $data['address']['contactperson'] = $address->getName();
            }

            /**
             * @var $ol Mage_Sales_Model_Order_Item
             */
            foreach ($order->getItemsCollection() as $ol) {
                if ($ol->isDeleted() || $ol->getProductType() !== 'simple') { 
                    continue;
                }

                $product = $this->getProduct($ol);

                $category_collection = array();
                if ($product) {
                    $category_ids = $product->getCategoryIds();
                    foreach ($category_ids as $category_id) {
                        $category = Mage::getModel('catalog/category')->load($category_id);
                        if ($category->getIsActive()) {
                            $category_collection[] = $category->getName();
                        }
                    }
                }

                // Add order line
                $data["order_lines"][] = array(
                    'article_code'          => $ol->getSku(),
                    'article_description'   => $product ? $product->getName() : '',
                    'quantity'              => (int)$ol->getData('qty_ordered'),
                    'title'                 => $product ? $product->getName() : '',
                    'description'           => $product ? $product->getDescription() : '',
                    'deep_url'              => $product ? $product->getProductUrl() : '',
                    'categories'            => $category_collection
                );

                try {
                    $data['image_url'] = $product->getImageUrl();
                } catch (Exception $e) {
                    // Do nothing
                }
            }

            $result = $this->call('/2/orders/create', $data);
            return $result;
        }

        return false;
    }

    /**
     * Use the API to get tracking for orders
     * Called by the Magento CRON
     * @param Mage_Sales_Model_Resource_Order_Collection $orders [description]
     */
    public function getOrderTracking(Mage_Sales_Model_Resource_Order_Collection $orders)
    {
        if ($this->isLoggedIn()) {
            foreach ($orders as $order) {
                $order_ids[] = $order->getRealOrderId();
            }

            $result = $this->call('/4/orders/tracking', array(
                'reference'=>$order_ids
            ));

            return $result;
        }
        return null;
    }

    /**
     * Write logs to the API for support purposes
     * @param string    $tag
     * @param array     $data
     * @param string    $order_id
     */
    public function writeLog($tag, $data, $order_id = null)
    {
        $params = array(
            'type'=>'magento',
            'tag'=>$tag,
            'data'=>$data
        );
        if (!is_null($order_id)) {
            $params['order_id'] = $order_id;
        }

        $this->call('/1/logs/write', $params);
    }

    /**
     * Tries to get related product from order line
     * @param Mage_Sales_Model_Order_Item $order_line
     * @return Mage_Catalog_Model_Product|null
     */
    private function getProduct(Mage_Sales_Model_Order_Item $order_line)
    {
        // get product
        $product = Mage::getModel('catalog/product')->load($order_line->getProductId());
        if (empty($product) || ($order_line->getProductType() == 'configurable')) {
            $options = $order_line->getProductOptions();
            if (count($options)) {
                $product = false;
                if (isset($options['simple_sku']) && !empty($options['simple_sku'])) {
                    $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $options['simple_sku']);
                } elseif (isset($options['info_buyRequest']) && isset($options['info_buyRequest']['product'])) {
                    $product = Mage::getModel('catalog/product')->load($options['info_buyRequest']['product']);
                } elseif (isset($options['super_product_config']) && isset($options['super_product_config']['product_id'])) {
                    $product = Mage::getModel('catalog/product')->load($options['super_product_config']['product_id']);
                }
            }
        }
        if ($product && $product->getId()) {
            return $product;
        }
        $sku = $order_line->getSku();
        $pid = Mage::getModel('catalog/product')->getIdBySku($sku);
        return $pid ? Mage::getModel('catalog/product')->load($pid) : null;
    }

    /**
     * split multiline magento address to address and house number
     * @var Mage_Sales_Model_Order_Address $address
     * @return array [0] address [1] house no
     */
    private function splitAddress(Mage_Sales_Model_Order_Address $address)
    {
        $result = $this->_parseAddress($address->getStreetFull());

        return array_values((array)$result);
    } // split_address

    /**
     * Parse address string to components and return the result
     * @return string
     *
     */
    private function _parseAddress($address)
    {
        $full_street = $address;

        list($street, $street_number, $street_number_suffix) = array("", "", "");

        if (preg_match("/^\\s*(.+)\\s+(\\d+)\\s*(\\S*\\s+\\d+\\s*\\S*)$/", $full_street, $street_elements)
                || preg_match("/^\\s*(.+)\\s+(\\d+)\\s*(,\\s*.*)$/", $full_street, $street_elements)
                || preg_match("/^\\s*(.+)\\s+(\\d+)\\s*(.*)$/", $full_street, $street_elements)
        ) {
            $street = $street_elements[1];
            $street_number = $street_elements[2];
            $street_number_suffix = trim($street_elements[3]);
        } elseif (preg_match("/^\\s*(\\d+)(\\S*)\\s+(.*)$/", $full_street, $street_elements)) {
            $street = $street_elements[3];
            $street_number = $street_elements[1];
            $street_number_suffix = $street_elements[2];
        } elseif (preg_match("/^\\s*(.+\\D)\\s*(\\d+)\\s*(\\D+\\s*\\d*\\s*\\S*)$/", $full_street, $street_elements)
                || preg_match("/^\\s*(.+\\D)\\s*(\\d+)\\s*(.*)$/", $full_street, $street_elements)
        ) {
            $street = $street_elements[1];
            $street_number = $street_elements[2];
            $street_number_suffix = trim($street_elements[3]);
        } else {
            $street = $full_street;
        }

        return (object) array(
            "street" => $street,
            "housenumber" => $street_number,
            "addition" => $street_number_suffix
        );
    }
}