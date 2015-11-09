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

class Balticode_Dpd_Model_Observer
    extends Varien_Event_Observer
{
    /**
     * Observer lunch when make a save on System -> Configuration
     * Run command to test API, If all correct update parcelStore list
     *
     * @param  Varien_Event_Observer $observer Object
     */
    public function changedSettings(Varien_Event_Observer $observer)
    {
        if (Mage::helper('dpd/data')->getConfigData('active')) {
            Mage::getModel('dpd/deliverypoints')->generateDeliveryPoints();
        }
    }

    /**
     * Save Delivery data to quote after save
     *
     * @param  [type] $observer [description]
     * @return [type]           [description]
     */
    public function quoteSaveAfter($observer)
    {
        $postData = $_POST;
        if (isset($postData['shipping_method'])) {
            $shipping_method = $postData['shipping_method']; //dpd_ps
            $method = Mage::getModel('dpd/carrier')->getMethod($shipping_method); //ps

            if (Mage::getModel('dpd/carrier')->getModelClass($method) !== false) {
                $parameters = Mage::getModel('dpd/carrier')->collectParams($method, $postData);
                $quote = Mage::getModel('sales/quote')
                    ->loadByIdWithoutStore($observer->getEvent()->getDataObject()->getData('entity_id'));
                if ($quote->getDpdDeliveryOptions() != serialize($parameters)) {
                    $quote->setDpdDeliveryOptions(serialize($parameters));
                    $quote->save();
                }
            }
        }
    }

    /**
     * Observer run when Create a new Order on FrontEnd or Backend
     * Set data to order from Quote
     */
    public function newOrderPlaceAfter($observer)
    {
        $orderId = $observer->getEvent()->getOrder()->getEntityId();
        $quoteId = $observer->getEvent()->getOrder()->getQuoteId();
        $quote = Mage::getModel('sales/quote')
            ->loadByIdWithoutStore($quoteId);
        $dpdDeliveryOptions = $quote->getDpdDeliveryOptions();
        $order = Mage::getModel('sales/order')
            ->load($orderId);
        $order->setDpdDeliveryOptions($dpdDeliveryOptions);
        $order->save();
    }

    /**
     * Observer to set or change description of carrier
     *
     * @param  Varien_Event_Observer $observer
     */
    public function saleOrderViewShippingInformation(Varien_Event_Observer $observer)
    {
        $orderId = $observer->getEvent()->getShippingInformation()->getId();
        $shipping_method = $observer->getEvent()->getShippingInformation()->getShippingMethod();
        $method = Mage::getModel('dpd/carrier')->getMethod($shipping_method);
        $shipingInformation = $observer->getEvent()->getShippingInformation();
        if (Mage::getModel('dpd/carrier')->getCode('method',$method) === false) {
            return false; //This is not my shipping method
        } else {
            //Load Model of current Carrier Courier or PS
            $carrierMethod = Mage::getModel('dpd/carrier')->getModelClass($method);
            //Get current description of carrier
            $currentDescription = $shipingInformation->_getShippingDescription();
            //Get description of own carrier method
            $carrierDescription = $carrierMethod->getShippingDescription($orderId, $currentDescription);
            //Set description
            $shipingInformation->_setShippingDescription($carrierDescription);
        }
    }

    /**
     * Observer to add buck action to sales/order list
     */
    public function addBulkAction($observer)
    {
        if (Mage::helper('dpd/data')->getConfigData('active')) {
            $block = $observer->getEvent()->getBlock();
            if (get_class($block) == 'Mage_Adminhtml_Block_Widget_Grid_Massaction'
                && $block->getRequest()->getControllerName() == 'sales_order') {
                $block->addItem('dpd_labels', array(
                    'label' => __('Print DPD Labels'),
                    'url' => Mage::app()->getStore()->getUrl('dpd/adminhtml_label/label'),
                ));
                $block->addItem('dpd_labels_mps', array(
                    'label' => __('Print MPS DPD Labels'),
                    'url' => Mage::app()->getStore()->getUrl('dpd/adminhtml_label/labelMps'),
                ));
                $block->addItem('dpd_manifest', array(
                    'label' => __('Print DPD Manifest'),
                    'url' => Mage::app()->getStore()->getUrl('dpd/adminhtml_manifest/Manifest'),
                ));
            }

            if (Mage::helper('dpd/data')->getConfigData('call_courrier_enable')) {
                if (get_class($block) == 'Mage_Adminhtml_Block_Sales_Order'
                    && $block->getRequest()->getControllerName() == 'sales_order') {
                    $block->addButton('calldpdcourier', array(
                        'label'     => __('Call DPD courier'),
                        'onclick'   => "showCarrierWindow()",
                        'class'     => 'callDpdCourier',
                    ));
                }
            }
        }
    }

    /**
     * Observer for Messages from heaven
     */
    public function preDispatch(Varien_Event_Observer $observer)
    {
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {
            $feedModel  = Mage::getModel('dpd/feed');
            $feedModel->checkUpdate();
        }
    }
}
