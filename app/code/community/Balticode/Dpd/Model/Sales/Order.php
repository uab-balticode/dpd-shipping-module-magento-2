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

class Balticode_Dpd_Model_Sales_Order
    extends Mage_Sales_Model_Order
{
    private $shippingDescription;

    /**
     * Overwrite to call dispatch event to change messages
     * 
     * @return string message
     */
    public function getShippingDescription()
    {
        //Mage::dispatchEvent('sales_order_view_shipping_information_before', array('shipping_information' => &$this));
        $this->_setShippingDescription(parent::getShippingDescription()); //get description from parent
        Mage::dispatchEvent('sales_order_view_shipping_information_after', array('shipping_information' => &$this));
        return $this->_getShippingDescription();
    }

    /**
     * Get Current Shipping Description
     * 
     * @return string
     */
    public function _getShippingDescription()
    {
        return $this->shippingDescription;
    }

    /**
     * Set Shipping Description
     * 
     * @param string $message some message without price
     */
    public function _setShippingDescription($message)
    {
        if (!is_string($message)) {
            return false;
        }

        $this->shippingDescription = $message;
        return $this;
    }
}
