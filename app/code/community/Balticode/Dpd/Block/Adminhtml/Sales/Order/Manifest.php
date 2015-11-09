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

class Balticode_Dpd_Block_Adminhtml_Sales_Order_Manifest
    extends Mage_Adminhtml_Block_Template
{
    /**
     * Construct load manifest template
     */
    public function _construct()
    {
        $this->setTemplate('dpd/sales/order/manifest.phtml');
        parent::_construct();
    }

    /**
     * Collect manifest order ids
     * @return array Order list
     */
    public function getOrdersIds()
    {
        $orderIds = $this->getParam('order_ids');
        if (!count($orderIds)) {
            $orderIds = array();
        }
        return $orderIds;
    }

    /**
     * Collect params from post data
     * @param  string $name grab attribute by this name
     * @return mix
     */
    public function getParam($name = '')
    {
        if (empty($name)) {
            return $this->getRequest()->getParams();
        } else {
            return $this->getRequest()->getParam($name);
        }
    }

    /**
     * Load order by id
     * @param string | int $orderId
     * @return object Mage_Core_Sales_Model_Order
     */
    public function getOrder($orderId)
    {
        return Mage::getModel('sales/order')->load($orderId);
    }

    /**
     * Get order data by Order Id
     * @param  string | int     $orderId
     * @param  string $key      Attribute name
     * @return mix              Attribute value
     */
    public function getOrderData($orderId, $key = '')
    {
        return $this->getOrder($orderId)->getData($key);
    }

    /**
     * Get order shipping data by Order id
     * @param  string | int     orderId
     * @param  string $key     Attribute name
     * @return mix          Attribute value
     */
    public function getOrderShippingData($orderId, $key = '')
    {
        return $this->getOrder($orderId)->getShippingAddress()->getData($key);
    }

    /**
     * Get order DPD shipping method type
     * value returns from Balticode_Dpd_Model_Carrier
     * @param  string | int     $orderId
     * @return string          Order type is PS or D-COD-B2C
     */
    public function getOrderType($orderId)
    {
        return Mage::getModel('dpd/carrier')->getType($orderId);
    }

    /**
     * Get Order Barcode from Order commment
     * @param  string | int     $orderId
     * @return array           DPD generated barcode
     */
    public function getOrderBarcode($orderId)
    {
        return Mage::getModel('dpd/carrier')->getBarcodeFromOrder($orderId);
    }

    public function getPickUpData($name = null)
    {
        $pickupData = array(
                'client' => Mage::helper('dpd/data')->getConfigData('pickup_address_name'),
                'client_id' => Mage::helper('dpd/data')->getConfigData('id'),
                'client_street' => Mage::helper('dpd/data')->getConfigData('pickup_address_street'),
                'client_vat_code' => Mage::helper('dpd/data')->getConfigData('pickup_vat_code'),
                'client_phone' => Mage::helper('dpd/data')->getConfigData('pickup_address_phone'),
                'client_city' => Mage::helper('dpd/data')->getConfigData('pickup_address_city'),
                'client_country_id' => Mage::helper('dpd/data')->getConfigData('pickup_address_country'),
                'client_post' => Mage::helper('dpd/data')->getConfigData('pickup_address_zip'),
            );
        if (isset($pickupData[$name])) {
            return $pickupData[$name];
        }
        return $pickupData;
    }

    public function getDpdRequisition($name = null)
    {
        $lang = strtolower(Mage::app()->getLocale()->getLocaleCode());
        $dpdRequisition = array(
            'lt_lt' => array(
                'name' => 'DPD LIETUVA',
                'pvm' => 'LT1163929217',
                'address' => 'LIEPKALNIO G. 180',
                'tel' => '+370 52106777',
                'fax' => '+370 52106740'),
            'lv_lv' => array(
                'name' => 'DPD LATVIJA',
                'pvm' => 'LV 40003393255',
                'address' => 'URIEKSTES 8A',
                'tel' => '+371 67 385 240',
                'fax' => '+371 67 387 288'),
            'default' => array(
                'name' => 'DPD LIETUVA',
                'pvm' => 'LT1163929217',
                'address' => 'LIEPKALNIO G. 180',
                'tel' => '+370 52106777',
                'fax' => '+370 52106740'),
        );
        if (isset($dpdRequisition[$lang][$name])) {
            return $dpdRequisition[$lang][$name];
        }
        return $dpdRequisition['default'][$name];
    }

    public function getManifestNr()
    {
        $current_pack_number = Mage::helper('dpd/data')->getConfigData('manifestnr');
        $path = 'carriers/'.strtolower(Mage::helper('dpd/data')->module).'/manifestnr';
        Mage::getModel('core/config')->saveConfig($path, $current_pack_number+1); //Increment packs
        Mage::app()->getStore()->resetConfig();
        $current_pack_number = str_pad($current_pack_number, 8, '0', STR_PAD_LEFT);
        return $current_pack_number;
    }
}
