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

class Balticode_Dpd_Model_Carriers_Courier_Courier
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    public $delivery_time = array(  '1' => '8:00 - 14:00',
                                    '2' => '9:00 - 13:00',
                                    '3' => '14:00 - 17:00',
                                    '4' => '14:00 - 18:00',
                                    '5' => '16:00 - 18:00',
                                    '6' => '18:00 - 22:00');
    public $key = 'classic';
    protected $_code = 'balticode_dpd';

    /**
     * Number is using when client is old and parcel Number calculation by one
     * So set this number to continue count
     * @var integer
     */
    private $addictional_order_number = 0;

    /**
     * Array of Payment methods
     * Disable send data to DPD API if order payment method written in this array
     * For Parcel Store method
     *
     * @var array
     */
    public $disable_dpd_delivery_method_on_payments = array();

    /**
     * List of payment methods who is cache on delivery, this using for generate parcel type
     *
     * @var array
     */
    public $cod_methods = array(
        'Cash On Delivery' => 'cashondelivery',
        'Balticode Cash On Delivery' => 'Balticode_Cashondelivery'
    );

    /**
     * Return available carrier methods
     *
     * @return Array list of carriers
     */
    public function getAllowedMethods()
    {
        return array(
            'standard'    =>  __('Courrier'),
        );
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

    /**
     * Return Delivery interval
     *
     * @param  string | int - of delivery id
     * @return mix -    string - filtered values;
     *                  Array - all values,
     *                  Boolean - not found id
     */
    public function getDeliveryTime($id_delivery = null)
    {
        $delivery_time = $this->delivery_time;
        if ($id_delivery === null)
            return $delivery_time;
        else
            if ($delivery_time[$id_delivery] !== null
                && (is_string($id_delivery) || is_int($id_delivery)))
                return $delivery_time[$id_delivery];
            else
                return false;
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
        if (isset($parameters['dpd_delivery_strip']))
            $return[$this->key]['dpd_delivery_strip'] = $parameters['dpd_delivery_strip'];
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
        $deliveryTimeId = $this->getDeliveryTimeByOrder($orderId);
        if ($deliveryTimeId !== false)
        {
            $label = 'Delivery time: ';
            return $currentDescription.' / '.__($label).$this->delivery_time[$deliveryTimeId];
        }
        return $currentDescription;
    }

    /**
     * Load order and get option of dpdDeliveryOption
     *
     * @param  string $orderId
     * @return string          delivery time id
     */
    public function getDeliveryTimeByOrder($orderId)
    {
        $order = Mage::getModel('sales/order')->load($orderId);
        $deliveryOptionSerialize = $order->getDpdDeliveryOptions();
        if ($deliveryOptionSerialize === null)
            return false;
        $deliveryOptionArray = unserialize($deliveryOptionSerialize);

        $optionKey = array_keys($deliveryOptionArray); //classic or PS
        if (count($optionKey))
            if ($optionKey[0] == $this->key)
                return $deliveryOptionArray[$this->key]['dpd_delivery_strip'];

        return false;
    }

    /**
     * Return Available Payment Method for order
     *
     * @param  string | int - Id Order
     * @return boolean
     */
    public function availablePaymentMethod($order_id)
    {
        $paymentCode = Mage::helper('dpd/data')->getPaymentCode($order_id);
        $return = !(in_array($paymentCode,
                            $this->disable_dpd_delivery_method_on_payments));
        return $return;
    }

    /**
     * Return is Cash On Delivery Payment Method of this order
     *
     * @param  int  $order_id
     * @return boolean
     */
    public function isCodMethod($order_id)
    {
        $paymentCode = Mage::helper('dpd/data')->getPaymentCode($order_id);
        return in_array($paymentCode, $this->cod_methods);
    }

    /**
     * Return type of method it is using for API request
     *
     * @param  [type] $order_id [description]
     * @return [type]          [description]
     */
    public function getType($order_id)
    {
        if ($this->isCodMethod($order_id))
            return 'D-COD-B2C'; //Classic with CashOnDelivery
        else
            return 'D-B2C'; //Classic
    }

    /**
     * Create array with parameters who need to send to API
     * If send multiple orders address id need to be same!
     *
     * @param  mix $ordersId order id or array id's
     * @return array           description of orders
     */
    public function returnDetails($ordersId, $action)
    {
        $deliveryTime = array();
        $time_interval['from'] = array();
        $time_interval['to'] = array();
        $weight = 0;
        $orderItems = 0;
        $ordersPrice = 0;
        //If given multiple orders validate to same shipping address
        if (is_array($ordersId))
        {
            foreach ($ordersId as $orderId)
            {
                //If this is array or object skip (this in case)
                if (is_array($orderId) || is_object($orderId))
                    continue;
                $order = Mage::getModel('sales/order')->load($orderId);
                $weight += $order->getWeight();
                $test[$order->getShippingAddress()->getCustomerAddressId()] = $orderId;
                $deliveryTime[] = $order->getDpdDeliveryOptions();
                $orderItems += count($order->getAllVisibleItems());
                $ordersPrice += (float)Mage::helper('dpd/data')
                ->convertCurrency(
                    (float)$order->getGrandTotal(),
                    $order->getOrderCurrencyCode()
                );
            }
            //Something is wrong we have different shipping address id
            if (count($test) !== 1)
                return false;
        } else {
            $order = Mage::getModel('sales/order')->load((int)$ordersId);
            $ordersPrice += (float)Mage::helper('dpd/data')
            ->convertCurrency(
                (float)$order->getGrandTotal(),
                $order->getOrderCurrencyCode()
            );
            $weight = $order->getWeight();
            $deliveryTime[] = $order->getDpdDeliveryOptions();
        }

        //collect delivery times
        foreach ($deliveryTime as $time)
        {
            $time = unserialize($time);
            if (!empty($time)) //if time is set
            {
                $time_strip = array_values($time); //unserialize
                $time_strip = array_values($time_strip[0]);
                $time_number = array_filter(explode('-', $this->delivery_time[$time_strip[0]]));
                $time_interval['from'][] = strtotime(trim($time_number[0]));
                $time_interval['to'][] = strtotime(trim($time_number[1]));
            }
        }

        $shippingAddress = $order->getShippingAddress();
        $num_of_parcel = ($action == 'join') ? '1' : $orderItems;

        $returnDetails = array(
            'name1' => $shippingAddress->getName(),
            'company' => $shippingAddress->getCompany(),
            'street' => $shippingAddress->getStreetFull(),
            'pcode' => preg_replace('/\D/', '', $shippingAddress->getPostcode()),
            'country' => strtoupper($shippingAddress->getCountryId()),
            'city' => $shippingAddress->getCity(),

            'weight' => ($weight)?$weight:'1',

            //'Sh_contact' => $shippingAddress->getName(),
            'phone' => $shippingAddress->getTelephone(),
        //    'remark' => $this->_getRemark($order),
         //   'Po_type' => $this->getConfigData('senddata_service'),
            'num_of_parcel' => $num_of_parcel,
            'order_number' => str_pad((int)$order->getIncrementId() + $this->addictional_order_number, 10, '0', STR_PAD_LEFT),
            'idm' => 'Y', //Parcelshop is required the idm parameters
            'idm_sms_rule' => 1, //Write the sum amount of the chosen SMS rules:
                            // 1 – pickup               0b1000000
                            // 2 – non delivery     0b0100000
                            // 4 – delivery         0b0010000
                            // 8 – inbound              0b0001000
                            // 16 – out for delivery    0b0000100
                            // 902 (when using PS type, then the value MUST be );
            'parcel_type' => (string)$this->getType($order->getId()),
            'action' => 'parcel_import'
        );

        if ($this->isCodMethod($order->getId())) {
            $returnDetails['cod_amount'] = (float)$ordersPrice;
        }

        if (count($ordersId) === count($time_interval['from'])
            && count($ordersId) === count($time_interval['to'])
            && count($time_interval['from']) > 0
            && count($time_interval['to']) > 0)
        {
            $returnDetails['timeframe_from'] = Mage::getModel('dpd/data')->toTimeStamp(date('H:i', min($time_interval['from'])), 'H:i'); //Syntax of the value HH24:MI, example 14:00
            $returnDetails['timeframe_to'] = Mage::getModel('dpd/data')->toTimeStamp(date('H:i', max($time_interval['to'])), 'H:i'); //Syntax of the value HH24:MI, example 14:00
        }

        return $returnDetails;
    }

    /**
     * Is enabled this method
     *
     * @return boolean
     */
    public function isEnabled()
    {
        $enabled = Mage::helper('dpd/data')->getConfigData('classic_enabled') //This method
                    & Mage::helper('dpd/data')->getConfigData('active'); //Global
        return $enabled;
    }

    public function getShippingRateResult($_code, $request)
    {
        $quote = $request['all_items'][0]->getQuote();

        $price = $this->getPrice($quote->getEntityId());
        if ($price === false) {//Method not available for this quote
            return false;
        }

        if (Mage::helper('dpd/data')->getConfigData('classic_sallowspecific')) { //Specific Country
            $country = explode(',', Mage::helper('dpd/data')->getConfigData('classic_specificcountry'));
            if (count($country)) {
                if (!in_array($quote->getShippingAddress()->getCountryId(), $country)) {
                    return false;
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
        $rate->setMethodTitle(Mage::helper('dpd/data')->getConfigData('classic_title'));
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
        $csvRestrictions = Mage::getModel('dpd/deliveryprice')->collectRestriction(
            preg_replace('/\D/', '', $quote->getShippingAddress()->getPostcode()),
            $this->key,
            Mage::app()->getWebsite()->getId()
        );

        //Found this postcode in CSV restrictions/options?
        if (count($csvRestrictions) && Mage::getModel('dpd/deliveryprice')->isEnabled()) {
            return $this->getCsvShippingCost($quote, $csvRestrictions);
        }

        //Calculate by country restriction
        if (Mage::helper('dpd/data')->getConfigData('classic_show_restrictions')) {//Enabled restrictions
            if ($this->isExistRestriction()) {
                return $this->getRestrictionCost($quote);
            }
        }

        //Calculate for basic settings
        if ((boolean)Mage::helper('dpd/data')->getConfigData('classic_free_enable')) { //Enabled free shipping?
            if ((float)$quote->getSubtotal()
                >= (float)Mage::helper('dpd/data')->getConfigData('classic_free_subtotal')) {
                return (float)0.0;
            }
        }

        return Mage::helper('dpd/data')->getConfigData('classic_price');
    }

    public function getRestrictions($id_country = null)
    {
        //Calculate by restriction
        $restrictionsSerial = Mage::helper('dpd/data')->getConfigData('classic_restrictions');
        $restrictions = Mage::getModel('dpd/data')->flipArrayList(unserialize($restrictionsSerial));
        //leave only for this country
        foreach ($restrictions as $key => $carrier_package_size_option) {
            $restrictions[$key]['base_price'] = (float)$carrier_package_size_option['base_price']; //String to float
            $restrictions[$key]['free_from_price'] = (float)$carrier_package_size_option['free_from_price']; //String to float

            $dimension = explode('X', strtoupper($carrier_package_size_option['dimensions']));
            array_filter($dimension);
            if (count($dimension) !== 3) {//If wrong parsing skip this line
                continue;
            }

            $restrictions[$key]['height']  = trim($dimension[0]);
            $restrictions[$key]['width'] = trim($dimension[1]);
            $restrictions[$key]['depth'] = trim($dimension[2]);
            if ($id_country === null) {
                $id_country = Mage::getSingleton('checkout/cart')->getQuote()
                 ->getShippingAddress()
                 ->getCountryId();
            }
            //Current restriction is for this country? if not, unset current option
            if (Mage::getModel('dpd/data')->recursive_array_search($id_country, $carrier_package_size_option) === false) {
                unset($restrictions[$key]);
            }
        }
        return $restrictions;
    }

    public function isExistRestriction($id_country = null, $carrier_type = 'CARRIER')
    {
        if (count($this->getRestrictions($id_country))) {
            return true;
        } else {
            return false;
        }
    }

    public function getRestrictionCost($quote)
    {
        $totalCartWeight = $quote->getShippingAddress()->getWeight();
        $cartProductsDimensions = $this->getCartProductsDimensions($quote);
        $restrictions = $this->getRestrictions();
        $correctRestriction = array();

        foreach ($restrictions as $currentRestriction) {
            if ((float)$currentRestriction['weight'] < (float)$totalCartWeight) {
                //This currentRestriction is smaller that we have, so we test it allowed to overweight.
                if ($currentRestriction['overweight_price'] > -1) { //Overweight is allowed?
                    if ((float)$currentRestriction['height'] < (float)max($cartProductsDimensions['height']) //if this restriction is to smaller that we want
                        || (float)$currentRestriction['width'] < (float)max($cartProductsDimensions['width']) //if this restriction is to smaller that we want
                        || (float)$currentRestriction['depth'] < (float)max($cartProductsDimensions['depth'])) {//if this restriction is to smaller that we want)
                        if ((float)$currentRestriction['oversized_price'] > -1) { //Oversize is allowed
                            $correctRestriction = $currentRestriction;
                        } else {
                            return false; //oversize is not available
                        }
                    } else {
                        $correctRestriction = $currentRestriction;
                    }
                }
                continue; //Stop foreach we do not need smaller restrictions
            } else {
                if ((float)$currentRestriction['height'] < (float)max($cartProductsDimensions['height']) //if this restriction is to big that we want
                   || (float)$currentRestriction['width'] < (float)max($cartProductsDimensions['width']) //if this restriction is to big that we want
                   || (float)$currentRestriction['depth'] < (float)max($cartProductsDimensions['depth'])) { //if this restriction is to big that we want
                    if ((float)$currentRestriction['oversized_price'] > -1) { //Oversize is allowed?
                        $correctRestriction = $currentRestriction;
                    } else {
                        return false; //oversize is not available
                    }
                } else {
                    if ((float)$currentRestriction['height'] >= (float)max($cartProductsDimensions['height']) //if this restriction is to big that we want
                       || (float)$currentRestriction['width'] >= (float)max($cartProductsDimensions['width']) //if this restriction is to big that we want
                       || (float)$currentRestriction['depth'] >= (float)max($cartProductsDimensions['depth'])) { //if this restriction is to big that we want
                        $correctRestriction = $currentRestriction;
                    }
                }
            }
        }
        //We no not found any restriction by this cart so this method is not available... return false
        if (!count($correctRestriction)) {
            return false;
        }

        //Now we have one restriction, from where need get price
        if ($correctRestriction['free_from_price'] > -1) { //If free shipping is allowed
            if ((float)$totalCartWeight >= $correctRestriction['free_from_price']) {
                return (float)0.0; //Return free shipping
            }
        }

        $currentPrice = (float)$correctRestriction['base_price']; //Apply base price

        //Do we have overweight?
        if ((float)$currentRestriction['weight'] < (float)$totalCartWeight) {
            if ($currentRestriction['overweight_price'] > 0) { //Test again we accept overweight?
                $currentPrice += (float)$currentRestriction['overweight_price']; //Plus overweight price
            }
        }

        //Do we have oversize?
        if ((float)$currentRestriction['height'] < (float)max($cartProductsDimensions['height']) //if this restriction is to smaller that we want
               || (float)$currentRestriction['width'] < (float)max($cartProductsDimensions['width']) //if this restriction is to smaller that we want
               || (float)$currentRestriction['depth'] < (float)max($cartProductsDimensions['depth'])) { //if this restriction is to smaller that we want
            if ($currentRestriction['oversized_price'] > 0) { //Test again we accept oversize?
                $currentPrice += (float)$currentRestriction['oversized_price']; //Plus oversize price
            }
        }

        return $currentPrice; //Return final price

    }

    /**
     * Get price from database restriction where imported per CSV file
     *
     * @param  string | int $quote        Current Quote Id
     * @param  Array $restrictions        Restriction who need to apply
     * @return Mix boolean - false if not available
     *         Float data of shipping price
     */
    private function getCsvShippingCost($quote, $restrictions)
    {
        $currentPrice = false;
        //Do we have settings for this postcode?
        if (!count($restrictions)) {
            return false;
        }

        //Grab restrictions by this post code carrier and shop
        $carrier_package_size = $restrictions;

        foreach ($carrier_package_size as $key => $row) {
            $weight[$key] = $row['weight'];
        }
        array_multisort($weight, SORT_DESC, $carrier_package_size);
        //SORTING FROM BIGER TO SMALLER

        $totalCartWeight = (float)$quote->getShippingAddress()->getWeight();
        $cartProductsDimensions = $this->getCartProductsDimensions($quote);

        $correctRestriction = array();
        foreach ($carrier_package_size as $currentRestriction) {
            if ((float)$currentRestriction['weight'] < (float)$totalCartWeight) {
                //This currentRestriction is smaller that we have, so we test it allowed to overweight.
                if ($currentRestriction['overweight_price'] > -1) {//Overweight is allowed?
                    if ((float)$currentRestriction['height'] < (float)max($cartProductsDimensions['height']) //if this restriction is to smaller that we want
                        || (float)$currentRestriction['width'] < (float)max($cartProductsDimensions['width']) //if this restriction is to smaller that we want
                        || (float)$currentRestriction['depth'] < (float)max($cartProductsDimensions['depth'])) {//if this restriction is to smaller that we want)
                        if ((float)$currentRestriction['oversized_price'] > -1) {//Oversize is allowed
                            $correctRestriction = $currentRestriction;
                        } else {
                            return false; //oversize is not available
                        }
                    } else {
                        $correctRestriction = $currentRestriction;
                    }
                }
                continue; //Stop foreach we do not need smaller restrictions
            } else {
                if ((float)$currentRestriction['height'] < (float)max($cartProductsDimensions['height']) //if this restriction is to big that we want
                   || (float)$currentRestriction['width'] < (float)max($cartProductsDimensions['width']) //if this restriction is to big that we want
                   || (float)$currentRestriction['depth'] < (float)max($cartProductsDimensions['depth'])) {//if this restriction is to big that we want
                    if ((float)$currentRestriction['oversized_price'] > -1) { //Oversize is allowed?
                        $correctRestriction = $currentRestriction;
                    } else {
                        return false; //oversize is not available
                    }
                } else {
                    $correctRestriction = $currentRestriction;
                }
            }
        }

        //We no not found any restriction by this cart so this method is not available... return false
        if (!count($correctRestriction))
            return false;

        //Now we have one restriction, from where need get price
        if ($correctRestriction['free_from_price'] > -1) { //If free shipping is allowed
            if ((float)$quote->getSubtotal() >= $correctRestriction['free_from_price']) {
                return (float)0.0; //Return free shipping
            }
        }

        $currentPrice = (float)$correctRestriction['price']; //Apply base price

        //Do we have overweight?
        if ((float)$currentRestriction['weight'] < (float)$totalCartWeight) {
            if ($currentRestriction['overweight_price'] > -1) { //Test again we accept overweight?
                $currentPrice += (float)$currentRestriction['overweight_price']; //Plus overweight price
            }
        }

        //Do we have oversize?
        if ((float)$currentRestriction['height'] < (float)max($cartProductsDimensions['height']) //if this restriction is to smaller that we want
               || (float)$currentRestriction['width'] < (float)max($cartProductsDimensions['width']) //if this restriction is to smaller that we want
               || (float)$currentRestriction['depth'] < (float)max($cartProductsDimensions['depth'])) { //if this restriction is to smaller that we want
            if ($currentRestriction['oversized_price'] > -1) {//Test again we accept oversize?
                $currentPrice += (float)$currentRestriction['oversized_price']; //Plus oversize price
            }
        }

        return $currentPrice; //Return final price
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
            $dimensions['diagonal'][$key] = (float)$currentProduct->getQty() * $this->getDiagonal(
                (float)$product->getPackageHeight(),
                (float)$product->getPackageWidth(),
                (float)$product->getPackageDepth()
            );
        }
        return $dimensions;
    }

    public function getRescrictionPrice($product, $rescriction, $cartPrice = 0)
    {
        $price = array(
                'regular' => (float)0,
                'additional' => (float)0,
            );

        if ($product['height'] <= $rescriction['dimensions']['height']
            && $product['width'] <= $rescriction['dimensions']['width']
            && $product['depth'] <= $rescriction['dimensions']['depth']) {//Package size is small
            if ($rescriction['free_from_price'] >= 0) //free shipping is enabled
                if ($cartPrice >= $rescriction['free_from_price']) {
                    $price['regular'] = (float)0.0; //Return free shipping
                    return $price;
                }

            $price['regular'] = (float)$rescriction['base_price']; //Return base shipping price
            return $price;
        } else {//Package size is to high
            if ((float)$rescriction['oversized_price'] == (float)-1) {//Oversize is not disallowed?
                return false; //Cannot ship this product
            } else { //Oversize is allowed
                if ($rescriction['oversized_price'] >= 0) {//Oversize price is correct?
                    if ($rescriction['free_from_price'] >= 0) //free shipping is enabled
                        if ($cartPrice >= $rescriction['free_from_price']) {
                            $price['regular'] = (float)0.0; //Return free shipping
                            return $price;
                        }
                    $price = array('regular' => (float)$rescriction['base_price'],
                                'additional' => (float)($rescriction['oversized_price'] * $product['quantity']));
                    return $price;
                } else {
                    self::log('getRescrictionPrice -> Oversize price is not correct given: '.$rescriction['oversized_price']);
                    return false;
                }
            }
        }
        return false;
    }
}
