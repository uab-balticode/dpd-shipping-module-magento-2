<?php
/**
 * 2015 UAB BaltiCode
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License available
 * through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@balticode.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to
 * newer versions in the future.
 *
 *  @author    UAB Balticode Kęstutis Kaleckas
 *  @package   Balticode_DPD
 *  @copyright Copyright (c) 2015 UAB Balticode (http://balticode.com/)
 *  @license   http://www.gnu.org/licenses/gpl-3.0.txt  GPLv3
 */

class Balticode_Dpd_Model_Carrier
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    /**
     * Shipping Method global code
     * (this code)_(methods)
     *
     * @var string
     */
    protected $_code = 'dpd';

    /**
     * CSV file prefix when exporting data
     *
     * @var array
     */
    public $csvHeader = array('postcode',
        'price',
        'free_from_price',
        'weight',
        'height',
        'width',
        'depth',
        'oversized_price',
        'overweight_price');

    /**
     * Message Prefix who set and later grab info from order messages
     * when searching barcode
     *
     * @var string
     */
    public $messagePrefix = 'DPD: ';

    /**
     * <p>%s in the URL is replaced with tracking number.</p>
     *
     * @var string
     */
    protected $_tracking_url = 'https://tracking.dpd.de/cgi-bin/delistrack?typ=1&lang=en&pknr=%s';

    /**
     * Get configuration data of carrier
     *
     * @param string $type
     * @param string $code
     * @return array|bool
     */
    public function getCode($type, $code = '')
    {
        static $codes;
        $codes = array(
            'method' => array(
                'classic' => Mage::helper('dpd')->__('CLASSIC'),
                'ps' => Mage::helper('dpd')->__('Parcel Store'),
            ),
            'frontend' => array(
                'classic' => $this->_code.'/classic', //Block/Classic.php
                'ps' => $this->_code.'/parcelstore', //Block/Parcelstore.php
            ),
            'class' => array(
                'classic' => 'Balticode_Dpd_Model_Carriers_Courier_Courier',
                'ps' => 'Balticode_Dpd_Model_Carriers_Parcelstore_Parcelstore',
                ),
            'adminhtml' => array(
                'classic' => $this->_code.'/adminhtml_sales_order_create_shipping_method_classic', //Block/Classic.php
                'ps' => $this->_code.'/adminhtml_sales_order_create_shipping_method_parcelstore', //Block/Parcelstore.php
                )
        );

        if (!isset($codes[$type])) {
            return false;
        } elseif ('' === $code) {
            return $codes[$type];
        }

        if (!isset($codes[$type][$code])) {
            return false;
        } else {
            return $codes[$type][$code];
        }
    }

    /**
     * Get allowed shipping methods
     * CLASSIC, PS
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        $allowed = explode(',', Mage::helper('dpd/data')->getConfigData('allowed_methods'));
        $arr = array();
        foreach ($allowed as $k) {
            if ($this->getModelClass($k)->isEnabled()) {
                $arr[$k] = $this->getCode('method', $k);
            }
        }
        return $arr;
    }

    /**
     * Price rules import to database from array
     *
     * @param  array $content restrictions by postCode size
     * @param  string $block   name of block who importing data
     * @return boolean
     */
    public function dataImport($content, $block = '')
    {
        if (!$this->validateArray($content)) {//All Array is healthy
            return false;
        }
        $header = $content[0];
        unset($content[0]); //we not need header of CSV

        $carrierBlock = explode('_', $block);
        $storeId =  Mage::helper('dpd/data')->getStoreId();
        $this->clearDeliveryPrice($carrierBlock['0'], $storeId);

        foreach ($content as $restriction) {
            $data = array_combine($header,$restriction);
            $data['carrier_id'] = $carrierBlock['0']; //Classic or Parcel Store
            $data['id_shop'] = $storeId;
            $deliveryprice = Mage::getModel('dpd/deliveryprice');
            if (!$deliveryprice->addRestriction($data)) {
                foreach ($deliveryprice->getErrorMessages() as $message) {
                    Mage::getSingleton('core/session')->addError($message);
                }
            }

            foreach ($deliveryprice->getWarningMessages() as $message) {
                Mage::getSingleton('core/session')->addWarning($message);
            }

        }
        return __CLASS__;
    }

    /**
     * Deleting old value from daabase
     *
     * @param  string | int $carrier_id Carrier id who has been uploding file
     * @param  string | int $store_id   store id where we set
     */
    private function clearDeliveryPrice($carrier_id, $store_id)
    {
        Mage::getModel('dpd/deliveryprice')->deleteRestrictions($carrier_id, $store_id);
    }

    /**
     * Validation restriction array
     * If importing data not right return false
     *
     * @param  array $restriction_array Restriction description
     * @return boolean
     */
    public function validateArray($restriction_array)
    {
        if (!is_array($restriction_array)) { //Is array given?
            return false;
        }
        foreach ($restriction_array as $line) {//All lines is unique how we want?
            if (count($this->csvHeader) !== count($line)) {
                return false;
            }
        }
        return true; //OK all is correct
    }

    /**
     * Return data about this shipping methods (price, name, etc.)
     *
     * @param  Mage_Shipping_Model_Rate_Request $request
     * @return object Mage_Core_Shipping_Model_Rate_Result with methods
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!Mage::helper('dpd/data')->getConfigData('active')) {
            return false;
        }

        $result = Mage::getModel('shipping/rate_result');
        foreach ($this->getAllowedMethods() as $method => $title) {
            $result->append($this->getModelClass($method)->getShippingRateResult($this->_code, $request));
        }

        return $result;
    }

    /**
     * Run this function from FrontEnd when selected Shipping method
     * this for addictional information about shipping method
     * (select time, select parcelstore, or write some import information)
     *
     * @param  string $code Method code
     * @param  string $area BackEnd - 'adminhtml' of FrontEnd - 'frontend'
     * @return string       Method Class Name
     */
    public function getFormBlock($code = null, $area = 'frontend')
    {
        if ($code == null) {
            return null;
        }

        $method = $this->getMethod($code);
        if (in_array($method, array_keys($this->getAllowedMethods()))) {
            return $this->getCode($area, $method);
        }
    }

    /**
     * return method name without carrier name
     *
     * @param  string $code Method code with NameSpace
     * @return string       Method code without NameSpace
     */
    public function getMethod($code)
    {
        return ltrim(
                str_replace($this->_code, '', $code),
                '_'
            );
    }

    /**
     * Load carrier Model and collect carrier data
     *
     * @param  string $method Method name without NameSpace needed
     * @return array
     */
    public function collectParams($method, $parameters)
    {
        $carrierModel = $this->getModelClass($method);
        if ($carrierModel !== false) {
            return $carrierModel->collectParams($parameters);
        }

        return array();
    }

    /**
     * Load Carrier Class
     *
     * @param  string $method Method name ps or classic
     * @return object         Selected class
     */
    public function getModelClass($method)
    {
        $class = $this->getCode('class', $method);

        return Mage::getModel($class);
    }

    /**
     * If carrier supports external tracking URL's then it should return true
     *
     * @return boolean
     */
    public function isTrackingAvailable()
    {
        if ($this->getTrackingUrl()) {
            return true;
        }

        return false;
    }

    /**
     * Returns tracking URL for current carrier if one exists.
     *
     * @return bool|string
     */
    public function getTrackingUrl()
    {
        return $this->_tracking_url;
    }

    public function getType($orderId)
    {
        $order = Mage::getSingleton('sales/order')->load($orderId);
        $shippingMethod = $order->getShippingMethod();
        $method = $this->getMethod($shippingMethod);
        $methodClass = $this->getModelClass($method);
        return $methodClass->getType($orderId);
    }

    /**
     * Add some message to order
     *
     * @param string - Order id
     * @param string - Some Message
     * @param string - Message prefix to indicate it's my message
     * @return boolean
     */
    public function addMessageToOrder($id_order, $message, $messagePrefix = null)
    {
        if ($messagePrefix == null) {
            $messagePrefix = $this->messagePrefix;
        }
        $order = Mage::getModel('sales/order')->load($id_order);
        $message = $messagePrefix.$message;
        $order->addStatusHistoryComment($message);
        $order->save();
        return $this;
    }

    /**
     * Return last Message from order where text start with prefix
     *
     * @param  string - Order id
     * @param  string|null - Message prefix if null use global class messageprefix
     * @param  boolean - Message type is only for administrator or and customer
     * @return array - Messages, if not found return empty array
     */
    private function getMessageFromOrder($id_order, $messagePrefix = null, $private = true)
    {
        if ($messagePrefix == null) {
            $messagePrefix = $this->messagePrefix;
        }

        $order = Mage::getModel('sales/order')->load($id_order);
        $messageText = array();
        foreach ($order->getAllStatusHistory() as $statusHistory) {
            /* @var $statusHistory Mage_Sales_Model_Order_Status_History */
            if ($statusHistory->getComment() && !$statusHistory->getVisibleOnFront()) {
                if (strpos($statusHistory->getComment(), $messagePrefix) !== false) {
                    $messageText[] = $statusHistory->getComment();
                }
            }
        }
        return $messageText;
    }

    /**
     * Return order has barcode (barcode message starts with some prefix)
     *
     * @param  string - Order id
     * @param  str|null - Message Prefix if null use global class messageprefix
     * @return boolean
     */
    public function hasBarcode($id_order, $messagePrefix = null)
    {
        if (!is_string($id_order) && !is_int($id_order)) {
            return false;
        }

        $barcode = $this->getBarcodeFromOrder($id_order, $messagePrefix);
        if (count($barcode)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return array Barcodes by Order id's
     *
     * @param  array  $id_orders [description]
     * @param  string | null $messagePrefix Message prefix if null use global class messageprefix
     * @return array - orderId => Barcode
     */
    public function getBarcodeByOrderIds($id_orders = array(), $messagePrefix = null)
    {
        if (!is_array($id_orders)) {
            return false;
        }

        $barcodes = array();
        foreach ($id_orders as $id_order) {
            if ($this->hasBarcode($id_order)) {
                $barcodes[$id_order] = $this->getBarcodeFromOrder($id_order, $messagePrefix);
            }
        }
        return $barcodes;
    }

    /**
     * Return single Barcode from order messages
     *
     * @param  string - Order id
     * @param  str|null -  Message prefix if null use global class messageprefix
     * @return string - Barcode without messagePrefix
     */
    public function getBarcodeFromOrder($id_order, $messagePrefix = null)
    {
        $messages = array();

        if (is_array($id_order) || is_object($id_order)) {
            return false;
        }

        if ($messagePrefix == null) {
            $messagePrefix = $this->messagePrefix;
        }

        foreach ($this->getMessageFromOrder($id_order, $messagePrefix) as $message) {
            $messages[] = ltrim($message, $messagePrefix);
        }

        return $messages;
    }

    /**
     * Write Message to Order info
     *
     * @param string - Order id
     * @param string - Barcode number
     * @param str|null - Message start with messagePrefix, if null using global class messagePrefix
     * @return Boolean
     */
    public function setBarcodeToOrder($id_order, $barcode, $messagePrefix = null)
    {
        if ($messagePrefix == null) {
            $messagePrefix = $this->messagePrefix;
        }

        return $this->addMessageToOrder($id_order, $barcode, $messagePrefix);
    }
}
