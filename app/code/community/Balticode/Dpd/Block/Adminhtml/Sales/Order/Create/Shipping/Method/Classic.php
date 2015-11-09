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

class Balticode_Dpd_Block_Adminhtml_Sales_Order_Create_Shipping_Method_Classic
    extends Mage_Adminhtml_Block_Sales_Order_Create_Shipping_Method_Form
{
    /**
     * Constructor to load template of frontend where selecting delivery time
     * if enabled of this method
     */
    public function __construct()
    {
        if (Mage::helper('dpd/data')->getConfigData('classic_show_delivery_time'))
            $this->setTemplate('dpd/sales/order/create/shipping/method/classic.phtml');
    }

    /**
     * Get all available times from Settings
     * @return array    Writed City and available times for this
     */
    private function getDeliveryTime()
    {
        $deliveryTime = Mage::helper('dpd/data')->getConfigData('classic_delivery_time');
        return unserialize($deliveryTime);
    }

    /**
     * Get available times strip by City
     * This is like getDeliveryTime just filtered
     * @param  string $deliveryShippinCity Name of city who need to be leave from all delivery times
     * @return array                      if current city not found in array return empty array
     *                                    if current city found return them
     */
    public function getDeliveryTimeAvailable($deliveryShippinCity = null)
    {
        if ($deliveryShippinCity === null)
            $deliveryShippinCity = $this->getQuoteShippingAddress('city');
        $deliverySettings = $this->getDeliveryTime();
        $line = Mage::getModel('dpd/data')->recursive_array_search(
                trim(strtolower($deliveryShippinCity)),
                Mage::getModel('dpd/data')->array_change_value_case($deliverySettings['city'])
            );
        if ($line !== false)
            return array_intersect_key(
                    Mage::getModel('dpd/carriers_courier_courier')->delivery_time,
                    array_flip($deliverySettings['time'][$line])
                );
        return array();
    }

    /**
     * Get Quote data
     *
     * @param String $column
     * @return Mix
     */
    public function getQuoteValue($column = '')
    {
        return $this->getQuote()->getData($column);
    }

    /**
     * Get Quote Shipping Address data
     *
     * @param String $column
     * @return Mix
     */
    private function getQuoteShippingAddress($column = '')
    {
        return $this->getQuote()->getShippingAddress()->getData($column);
    }

    /**
     * Get URL to controller where send data to DPD API
     * @return [type] [description]
     */
    public function getOrderSaveControllerUrl()
    {
        $values = array(
            'form_key' => Mage::getSingleton('core/session')->getFormKey()
        );

        return Mage::helper('adminhtml')->getUrl("dpd/adminhtml_salesorder/save/", $values).'?isAjax=1';
    }

    /**
     * Get current value from database, if not set return 0
     *
     * @return string  parcelStore Id
     */
    public function getCurrentValue()
    {
        $dpd_delivery_options = $this->getQuoteValue('dpd_delivery_options');
        $dpd_delivery_options = unserialize($dpd_delivery_options);
        $methodKey = Mage::getSingleton('dpd/carriers_courier_courier')->key;
        if ($dpd_delivery_options)
            foreach ($dpd_delivery_options as $deliveryType => $deliveryOption)
                if ($deliveryType == $methodKey)
                    return (string)$deliveryOption['dpd_delivery_strip'];
        return '0';
    }
}
