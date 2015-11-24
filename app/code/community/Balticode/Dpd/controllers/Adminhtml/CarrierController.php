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

class Balticode_Dpd_Adminhtml_CarrierController
    extends Mage_Adminhtml_Controller_Action
{

    /**
     * Collect post arguments
     *
     * @param Key of array
     * @return Array
     */
    private function _collectPostData($post_key = null)
    {
        return $this->getRequest()->getPost($post_key);
    }

    public function CallAction()
    {
        $this->sendDataToCarrier();
        $this->goBack();
    }

    /**
     * Function send data to DPD API about carrier pickup
     */
    private function sendDataToCarrier()
    {
        $api = Mage::helper('dpd/api');

        $parameters = array(
            'action' => 'dpdis/pickupOrdersSave',
            'payerName' =>  Mage::helper('dpd/data')->getConfigData('pickup_address_name'),
            'senderName' => Mage::helper('dpd/data')->getConfigData('pickup_address_name'),
            'senderContact' => Mage::helper('dpd/data')->getConfigData('pickup_address_name'),
            'senderAddress' => Mage::helper('dpd/data')->getConfigData('pickup_address_street'),
            'senderPostalCode' => Mage::helper('dpd/data')->getConfigData('pickup_address_zip'),
            'senderCountry' => Mage::helper('dpd/data')->getConfigData('pickup_address_country'),
            'senderCity' => Mage::helper('dpd/data')->getConfigData('pickup_address_city'),
            'senderPhone' => Mage::helper('dpd/data')->getConfigData('pickup_address_phone'),
            'parcelsCount' => $this->_collectPostData('Po_parcel_qty'),
            'palletsCount' => $this->_collectPostData('Po_pallet_qty'),
            'nonStandard' => $this->_collectPostData('Po_remark'),
        );

        $responce = $api->postData($parameters);
        if (strip_tags($responce) == 'DONE') {
            Mage::getSingleton('adminhtml/session')->addSuccess(__('Call courier success'));
        } else {
            Mage::getSingleton('adminhtml/session')->addError(__('Call courier error: '.strip_tags($responce)));
        }
    }

    /**
     * Redirect to page from where come
     */
    private function goBack()
    {
        $this->_redirectReferer();
    }
}
