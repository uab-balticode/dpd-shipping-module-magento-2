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

class Balticode_Dpd_Block_Parcelstore
    extends Mage_Checkout_Block_Onepage_Shipping_Method_Available
{
    /**
     * Constructor to load template of frontend where selecting delivery time
     * if enabled of this method
     */
    public function __construct()
    {
        $this->setTemplate('dpd/parcelstore.phtml');
    }

    /**
     * Return available ParcelStores from database
     * Filtered ParcelStores by country_id is by LT LV or EE
     * Sort Delivery points by Priority get from Backend settings
     * 
     * @return array data about available ParcelStores
     */
    public function getParcelStore()
    {
        $deliveryPoints = Mage::getModel('dpd/deliverypoints');
        $parcelStores = $deliveryPoints->getDeliveryPoints($this->getQuoteShippingAddress('country_id'));
        $cityPriority = Mage::helper('dpd/data')->getConfigData('parcelstore_city_priority');
        $deliveryPoints->sortDeliveryPoints($parcelStores, explode(',', $cityPriority));
        return $parcelStores;
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
     * Show long or short Parcel Store names
     * Short - Just company name
     * Long - Show and with street address
     * 
     * @return boolean
     */
    public function showLongNames()
    {
        return Mage::helper('dpd/data')->getConfigData('parcelstore_long_address');
    }
}
