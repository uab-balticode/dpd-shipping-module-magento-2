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

class Balticode_Dpd_Block_Classic
    extends Mage_Checkout_Block_Onepage_Shipping_Method_Available
{
    /**
     * Constructor to load template of frontend where selecting delivery time
     * if enabled of this method
     */
    public function __construct()
    {
        if (Mage::helper('dpd/data')->getConfigData('classic_show_delivery_time'))
            $this->setTemplate('dpd/classic.phtml');
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
     * Get Quote Shipping Address data
     *
     * @param String $column
     * @return Mix
     */
    private function getQuoteShippingAddress($column = '')
    {
        return $this->getQuote()->getShippingAddress()->getData($column);
    }
}
