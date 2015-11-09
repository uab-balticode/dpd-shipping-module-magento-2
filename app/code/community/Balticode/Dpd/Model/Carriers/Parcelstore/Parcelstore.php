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

class Balticode_Dpd_Model_Carriers_Parcelstore_Parcelstore
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    public $maxWeight = 20; //Maximum weight shipping of this carrier
    public $maxLength = 100; //cm (any side)
    public $maxScope = 200; //cm

    public $key = 'ps';
    protected $_code = 'balticode_dpd';

    /*
    Array of Payment methods
    Disable send data to DPD API if order payment method written in this array
    For Parcel Store method
     */
    public $disable_dpd_delivery_method_on_payments = array(
        'Cash On Delivery' => 'cashondelivery',
        'Balticode Cash On Delivery' => 'Balticode_cashondelivery'
        );

    /**
     * Return available carrier methods
     *
     * @return Array list of carriers
     */
    public function getAllowedMethods()
    {
        return array(
            'standard'     =>  __('Parcel Store'),
        );
    }

    /**
     * Collect using data and return
     *
     * @param  [type] $parameters [description]
     * @return [type]             [description]
     */
    public function collectParams($parameters)
    {
        $return = array();
        if (isset($parameters['dpd_delivery_id'])) {
            $return[$this->key]['dpd_delivery_id'] = $parameters['dpd_delivery_id'];
        }
        return $return;
    }

    /**
     * Create description of carrier
     *
     * @param  string $currentDescription some text already Write of parent class
     * @return string                     new text for this description
     */
    public function getShippingDescription($orderId, $currentDescription = '')
    {
        $parcelStoreId = $this->getParcelStoreIdByOrder($orderId);
        if ($parcelStoreId !== false) {
            $parcelStore = Mage::getModel('dpd/deliverypoints')->getDeliveryPoints(false, false, $parcelStoreId);
            $parcelStore = array_values($parcelStore);
            $parcelStore = array_values($parcelStore[0]);
            $label = 'Selected Parcel Store: ';
            $psName = $parcelStore[0]['city'].' - '
                    .$parcelStore[0]['company']
                    .' '.$parcelStore[0]['street']
                    .' '.$parcelStore[0]['country']
                    .'-'.$parcelStore[0]['pcode']
                    .' '.$parcelStore[0]['phone'];
            $psName = preg_replace('!\s+!', ' ', $psName); //Multi space replace to single space

            return $currentDescription.' / '.__($label).$psName;
        }

        return $currentDescription;
    }

    /**
     * Load order and get option of dpdDeliveryOption
     *
     * @param  string $orderId
     * @return string          delivery parcelStore id
     */
    public function getParcelStoreIdByOrder($orderId)
    {
        $order = Mage::getModel('sales/order')->load($orderId);
        $deliveryOptionSerialize = $order->getDpdDeliveryOptions();
        if ($deliveryOptionSerialize === null) {
            return false;
        }
        $deliveryOptionArray = unserialize($deliveryOptionSerialize);
        $optionKey = array_keys($deliveryOptionArray); //classic or PS
        if ($optionKey[0] == $this->key) {
            return $deliveryOptionArray[$this->key]['dpd_delivery_id'];
        } else {
            return false;
        }
    }

    /**
     * Return Available Payment Method for order
     *
     * @param  string | int - Id Order
     * @return boolean
     */
    public function availablePaymentMethod($orderId)
    {
        $order = Mage::getSingleton('sales/order')->load($orderId);
        $return = !(in_array($order->getPayment()->getMethodInstance()->getCode(),
                            $this->disable_dpd_delivery_method_on_payments));
        return $return;
    }

    /**
     * Return type of method it is using for API request
     *
     * @param  [type] $orderId [description]
     * @return [type]          [description]
     */
    public function getType($orderId)
    {
        return 'PS';
    }

    /**
     * Create array with parameters who need to send to API
     *
     * @param  string $ordersId order id
     * @return array           description of orders
     */
    public function returnDetails($orderId)
    {
        $order = Mage::getModel('sales/order')->load($orderId);
        $shippingAddress = $order->getShippingAddress();
        $parcelStoreId = $this->getParcelStoreIdByOrder($orderId);
        if ($parcelStoreId === false) {
            return false;
        }

        $parcelStore = array();
        $parcelStore = Mage::getModel('dpd/deliverypoints')->getDeliveryPoints(false, false, $parcelStoreId);
        $parcelStore = array_values($parcelStore);
        $parcelStore = array_values($parcelStore[0]);
        $returnDetails = array(
            'name1' => $shippingAddress->getName(),
            'name2' => $parcelStore[0]['company'],
            'street' => $parcelStore[0]['street'],
            'pcode' => preg_replace('/\D/', '', $parcelStore[0]['pcode']),
            'country' => strtoupper($parcelStore[0]['country']),
            'city' => $parcelStore[0]['city'],
            'phone' => $shippingAddress->getTelephone(),
            'parcelshop_id' => $parcelStoreId,
        //    'remark' => $this->_getRemark($order),
        //    'Po_type' => $this->getConfigData('senddata_service'),
            'num_of_parcel' => '1',
            'order_number' => str_pad((int)$order->getIncrementId(), 10, '0', STR_PAD_LEFT),
            'idm' => 'Y', //Parcelshop is required the idm parameters
            'idm_sms_rule' => 902, //Write the sum amount of the chosen SMS rules:
                            // 1 – pickup               0b1000000
                            // 2 – non delivery     0b0100000
                            // 4 – delivery         0b0010000
                            // 8 – inbound              0b0001000
                            // 16 – out for delivery    0b0000100
                            // 902 (when using PS type, then the value MUST be );
            'parcel_type' => (string)$this->getType($order->getId()),
            'action' => 'parcel_import',
        );
        return $returnDetails;
    }

    /**
     * Is enabled this method
     *
     * @return boolean
     */
    public function isEnabled()
    {
        $eabled = Mage::helper('dpd/data')->getConfigData('parcelstore_enabled') //This method
                    & Mage::helper('dpd/data')->getConfigData('active'); //Global
        return $eabled;
    }

    public function getShippingRateResult($_code, $request)
    {
        $quote = $request['all_items'][0]->getQuote();
        $quoteId = $request['all_items'][0]->getQuote()->getEntityId();
        $price = $this->getPrice($quoteId);
        if ($price === false) {//Method not available for this quote
            return false;
        }

        if (Mage::helper('dpd/data')->getConfigData('parcelstore_sallowspecific')) {//Specific Country
            $country = explode(',', Mage::helper('dpd/data')->getConfigData('parcelstore_specificcountry'));
            if (count($country)) {
                if (method_exists($quote, 'getShippingAddress')) {
                    if (!in_array($quote->getShippingAddress()->getCountryId(), $country)) {
                        return false;
                    }
                }
            } else {
                return false; //Nothing select
            }
        }

        /** @var Mage_Shipping_Model_Rate_Result_Method $rate */
        $rate = Mage::getModel('shipping/rate_result_method');
        $rate->setCarrier($_code);
        $rate->setMethod($this->key);
        $rate->setCarrierTitle('DPD');
        $rate->setMethodTitle(Mage::helper('dpd/data')->getConfigData('parcelstore_title'));
        $rate->setPrice($price);
        $rate->setCost($price);
        return $rate;
    }

    /**
     * Get Price to this shipping method
     *
     * @param  sting | int $quoteId Current Quote Id
     * @return Mix boolean - false if not available
     *         Float data of shipping price
     */
    public function getPrice($quoteId)
    {
        $quote = Mage::getModel('sales/quote')->load($quoteId);
        $totalCartWeight = (float)$quote->getShippingAddress()->getWeight();
        //Order is to heavy
        if ($totalCartWeight > (float)$this->maxWeight) {
            return false;
        }

        $cartProductDimensions = $this->getCartProductsDimensions($quote);
        if (!count($cartProductDimensions['height'])
            || !count($cartProductDimensions['width'])
            || !count($cartProductDimensions['depth'])) {
                return (float)Mage::helper('dpd/data')->getConfigData('parcelstore_price');
            }

        $cartHeight = max($cartProductDimensions['height']);
        $cartWidth = max($cartProductDimensions['width']);
        $cartDepth = max($cartProductDimensions['depth']);
        $cartScope = ($cartHeight + $cartWidth) * 2 + $cartDepth;

        //Order is to Big
        if ((max($cartHeight, $cartWidth, $cartDepth) > $this->maxLength)
            || $cartScope > $this->maxScope) {
            return false;
        }

        if ((boolean)Mage::helper('dpd/data')->getConfigData('parcelstore_free_enable')) {//Enabled free shipping?
            if ((float)$quote->getSubtotal()
                >= (float)Mage::helper('dpd/data')->getConfigData('parcelstore_free_subtotal')) {
                return (float)0.0;
            }
        }

        return (float)Mage::helper('dpd/data')->getConfigData('parcelstore_price');
    }

    /**
     * Return cart products with dimensions
     *
     * @param  quote (object)
     * @return array
     */
    public function getCartProductsDimensions($quote)
    {
        $dimensions = array(
                'id_product' => array(),
                'quantity' => array(),
                'height' => array(),
                'width' => array(),
                'depth' => array(),
                'diagonal' => array(),
            );

        $quoteProducts = $quote->getAllItems();
        $catalogProduct = Mage::getModel('catalog/product');

        $returnItems = array();
        foreach ($quote->getAllVisibleItems() as $item) {
            if ($item->getHasChildren()) {
                foreach ($item->getChildren() as $child) {
                    $returnItems[] = $child;
                }
            } else {
                $returnItems[] = $item;
            }
        }

        foreach ($returnItems as $key => $currentProduct) {
            $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $currentProduct->getSku());
            $dimensions['id_product'][$key] = (int)$product->getId();
            $dimensions['quantity'][$key] = (float)$currentProduct->getQty();
            $dimensions['height'][$key] = (float)$product->getPackageHeight();
            $dimensions['width'][$key] = (float)$product->getPackageWidth();
            $dimensions['depth'][$key] = (float)$product->getPackageDepth();
            $dimensions['diagonal'][$key] = (float)$currentProduct->getQty() * $this->getDiagonal((float)$product->getPackageHeight(),
                                                                                        (float)$product->getPackageWidth(),
                                                                                        (float)$product->getPackageDepth());
        }
        return $dimensions;
    }

    /**
     *
     *                    Diagonal
     *                       |
     *                  *****|**********
     *                *.\    |       * *
     *              *  . \ <-|     *   *
     *            *    .  \      *     *
     *           ****************      *    <- Height
     *           *     .    \   *      *
     *           *     ......\..*......*
     *           *   .        \ *    *
     *           * .           \*  *    <- Depth
     *           ****************
     *                 Width
     *
     */
    public function getDiagonal($height, $width, $depth)
    {
        $height = trim($height);
        $width = trim($width);
        $depth = trim($depth);
        $dimensions = sqrt(($height * $height) + ($width * $width) + ($depth * $depth));
        return $dimensions;
    }

    /**
     * Return data about this shipping method (price, name, etc.)
     *
     * @param  Mage_Shipping_Model_Rate_Request $request
     * @return object Mage_Core_Shipping_Model_Rate_Result with parameters
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        $result = Mage::getModel('shipping/rate_result');

        $result->append($this->getShippingRateResult());
        return $result;
    }
}
