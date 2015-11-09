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

class Balticode_Dpd_Adminhtml_LabelController
    extends Mage_Adminhtml_Controller_Action
{
    private $dpd_available_parcel_types = array(
        array('service_code' => '803',
            'servce_description' => 'Parcel Shop',
            'parcel_type' => 'PS',
            'service_elements' => '601',
            'service_mark' => '',
            'service_text' => 'PS'),

        array('service_code' => '329',
            'servce_description' => 'Normal Parcel, COD, B2C',
            'parcel_type' => 'D-COD-B2C',
            'service_elements' => '001,013,100',
            'service_mark' => '',
            'service_text' => 'D-COD-B2C'),

        array('service_code' => '327',
            'servce_description' => 'Normal Parcel, B2C',
            'parcel_type' => 'D-B2C',
            'service_elements' => '1,013',
            'service_mark' => '',
            'service_text' => 'D-B2C'),
        );

    public $errorMessages = array('error' => array(), 'warning' => array());

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

    /**
     * Return DPD Label from API if Available
     *
     * @param  array of parameters important is orders id
     * @return mix if false some error is, else go to Barcode PDF content
     */
    public function LabelAction($orders = array())
    {

        //if we not set any orders, get from post
        if (!count($orders)) {
            $orders = $this->_collectPostData('order_ids');
        }
        if (!is_array($orders)) {
            $this->registerError(__('Please select Orders!'));
            $this->goBack();
            return false;
        }

        $availableOrders = $this->validateParams($orders); //select only available orders
        if ($availableOrders === false) {//If something wrong return false
            $this->goBack();
            return false;
        }

        $this->generateBarcode($availableOrders, 'join'); //Generate Barcodes from available orders
        $pdfContent = $this->generateLabels($availableOrders); //Get Label PDF content

        if (Mage::helper('dpd/api')->is_pdf($pdfContent)) {
            $this->_prepareDownloadResponse($this->getLabelFileName(), $pdfContent, 'application/pdf');
        } else {
            $this->registerError(__('Error: DPD API return not PDF file format'));
            $this->goBack();
        }
    }

    public function LabelMpsAction($orders = array())
    {
        //if we not set any orders, get from post
        if (!count($orders)) {
            $orders = $this->_collectPostData('order_ids');
        }
        if (!is_array($orders)) {
            $this->registerError(__('Please select Orders!'));
            $this->goBack();
        }

        $availableOrders = $this->validateParams($orders); //select only available orders
        if ($availableOrders === false) {//If something wrong return false
            $this->goBack();
            return false;
        }

        $this->generateBarcode($availableOrders, 'split'); //Generate Barcodes from available orders
        $pdfContent = $this->generateLabels($availableOrders); //Get Label PDF content

        if (Mage::helper('dpd/api')->is_pdf($pdfContent)) {
            $this->_prepareDownloadResponse($this->getLabelFileName(), $pdfContent, 'application/pdf');
        } else {
            $this->registerError(__('Error: DPD API return not PDF file format'));
            $this->goBack();
        }
    }

    /**
     * Validating of orders is all correct to get Labels
     *
     * @param  array $orders - validating orders
     * @return array | Boolean - array of available orders id; false - something wrong
     */
    public function validateParams(array $orders)
    {
        if (!is_array($orders)) {
            return false;
        }
        if (!count($orders)) {
            $this->registerError(__('Please select Orders!'));
        }

        $availableOrders = array();

        foreach ($orders as $key => $order) {
            if ($this->getAvailableCarrier($order)) { //This order is DPD method?
                $availableOrders[] = $order;
            }
        }

        if (!count($availableOrders)) {
            $this->registerError(__('Wrong select Orders!'));
            return false;
        }
        return $availableOrders;
    }

    /**
     * Save file content to temp folder to not lost data
     *
     * @param  string $fileConent some file content who need to be saved
     * @return string full path to file with file name
     */
    private function saveFileContent($fileConent)
    {
        $pathToFile = Mage::getBaseDir('tmp').'/dpd_'.time().'_label.pdf';
        $writedBytes = file_put_contents($pathToFile, $fileConent);
        if ($writedBytes === false) {
            $this->registerError(__('Error do not have permission to write to this folder').': '.Mage::getBaseDir('tmp'));
            return false;
        }
        return $pathToFile;
    }

    /**
     * Return generated Labels file name
     *
     * @param  array  $parameters some order parameters is for future
     * @return string Label file name
     */
    private function getLabelFileName($parameters = array())
    {
        $parameters; //This is for validation;
        $staticString = 'Labels-';
        $time = date('Ymd_H-i', time());
        return $staticString.$time.'.pdf';
    }

    /**
     * Return Labels from DPD API by available orders id
     *
     * @param  array  $availableOrders - Orders id
     * @return string - Label content
     */
    private function generateLabels($availableOrders = array())
    {
        $orderBarcodeList = Mage::getModel('dpd/carrier')->getBarcodeByOrderIds($availableOrders);
        //Unique barcodes by all orders (MPS has one barcode for multiple orders)
        //$barcodes = array_filter(array_unique(array_values($barcodes)));
        $barcodes = array();
        foreach ($orderBarcodeList as $orderId => $barcodeList) {
            foreach ($barcodeList as $barcode) {
                $barcodes[] = $barcode;
            }
        }

        $barcodes = array_filter(array_unique($barcodes));

        $returnDetails = array(
                'action' => 'parcel_print',
                'parcels' => join('|', $barcodes),
            );

        $api = Mage::helper('dpd/api');
        $apiReturn = $api->postData($returnDetails);

        if ($apiReturn === false) {
            foreach ($api->getErrorMessages() as $errorMessage) {
                $this->registerError($errorMessage);
            }
            return false;
        }

        if (!$api->is_pdf($apiReturn)) {
            $this->registerError(__('Error: DPD API return not PDF file format'));
            return false;
        }
        return $apiReturn;
    }

    /**
     * Return available currier to get some info for e.g. Labels
     * Order currier method is a DPD method?
     *
     * @param  string - Order Id
     * @return Boolean - this method is DPD method?
     */
    public function getAvailableCarrier($order_id)
    {
        $order = Mage::getModel('sales/order')->load($order_id);
        $carrier = Mage::getModel('dpd/carrier');
        $shippingMethod = $order->getShippingMethod();
        $method = $carrier->getMethod($shippingMethod);
        //This is a DPD delivery method?
        if ($carrier->getCode('method', $method) === false) {
            $this->registerWarning(__('Order: ').$order_id.__(' is not a DPD shipping method'));
            return false;
        }

        //Available Payment for this delivery payment?
        $carrierMethod = $carrier->getModelClass($method);
        if ($carrierMethod->availablePaymentMethod($order_id) === false) {
            $this->registerWarning(__('Order: ').$order_id.__(' Payment is not available for this delivery method'));
            return false;
        }
        return true;
    }

    /**
     * Add error text to Array
     *
     * @param  string - Error Message
     * @return mix - Self class;
     */
    public function registerError($errorMessage)
    {
        Mage::getSingleton('adminhtml/session')->addError($errorMessage);
        return __CLASS__;
    }

    /**
     * Add Warning text to array
     *
     * @param  string -  Warning Messages
     * @return mix - self class;
     */
    public function registerWarning($warningMessage)
    {
        Mage::getSingleton('adminhtml/session')->addWarning($warningMessage);
        return __CLASS__;
    }

    /**
     * Generate of Barcode, Send data about order to DPD API
     * Grab errors or Barcode number jSon format
     *
     * @param  array - all available orders id
     * @return mix
     */
    private function generateBarcode($availableOrders = array(), $action)
    {
        if (!count($availableOrders)) {//If no orders id found return false
            return false;
        }

        $differenceOrders = $this->groupOrders($availableOrders, $action); //group orders this for MPS method
        $api = Mage::helper('dpd/api');
        //Generate Barcodes by single orders
        foreach ($differenceOrders['single'] as $id_delivery => $typed_orders) {
            foreach ($typed_orders as $type_order => $id_order) {
                if (Mage::getModel('dpd/carrier')->hasBarcode($id_order[0])) {
                    continue;
                }

                $order = Mage::getModel('sales/order')->load($id_order[0]);
                $carrier = Mage::getModel('dpd/carrier');
                $method = $carrier->getMethod($order->getShippingMethod());
                $carrierMethod = $carrier->getModelClass($method);
                $returnDetails = $carrierMethod->returnDetails($id_order, $action);
                if (!is_array($returnDetails)) {
                    $this->registerError(__('Lost some data to format DataSend array'));
                    return false;
                }
                $apiReturn = $api->postData($returnDetails); //Get data form DPD API
                if ($apiReturn === false) {
                    foreach ($api->getErrorMessages() as $errorMessage) {
                        Mage::getModel('dpd/carrier')->addMessageToOrder((int)$id_order[0], $errorMessage, 'DPD ERROR: ');
                        $this->registerError($errorMessage);
                    }
                    return false;
                }
                if ($apiReturn->status == 'ok') {
                    if (!empty($apiReturn->errlog)) {
                        $this->registerWarning($apiReturn->errlog);
                    }
                    foreach ($apiReturn->pl_number as $barcode) {
                        Mage::getModel('dpd/carrier')->setBarcodeToOrder((int)$id_order[0], $barcode);
                    }
                }
            }
        }
        //Generate Barcodes by multiple orders -> MPS (join)
        foreach ($differenceOrders['multi'] as $id_delivery => $typed_orders) {
            foreach ($typed_orders as $type_order => $ordersIds) {
                //We need just first another else is same address because it is a MPS
                $firstOrderId = $typed_orders[$type_order][0];

                $order = Mage::getModel('sales/order')->load($firstOrderId);
                $carrier = Mage::getModel('dpd/carrier');
                $method = $carrier->getMethod($order->getShippingMethod());
                $carrierMethod = $carrier->getModelClass($method);
                $returnDetails = $carrierMethod->returnDetails($ordersIds, $action);
                if (!is_array($returnDetails)) {
                    $this->registerError(__('Lost some data to format DataSend array'));
                    return false;
                }
                $apiReturn = $api->postData($returnDetails); //Get data form DPD API
                if ($apiReturn === false) {
                    foreach ($api->getErrorMessages() as $errorMessage) {
                        $this->registerError($errorMessage);
                        foreach ($typed_orders as $id_orders) {
                            foreach ($apiReturn->pl_number as $barcode) {
                                foreach ($id_orders as $id_order) {
                                    Mage::getModel('dpd/carrier')->addMessageToOrder((int)$id_order, $errorMessage, 'DPD ERROR: ');
                                }
                            }
                        }
                    }
                    return false;
                }
                if ($apiReturn->status == 'ok') {
                    if (!empty($apiReturn->errlog)) {
                        $this->registerWarning($apiReturn->errlog);
                    }

                    foreach ($typed_orders as $id_orders) {
                        foreach ($apiReturn->pl_number as $barcode) {
                            foreach ($id_orders as $id_order) {
                                Mage::getModel('dpd/carrier')->setBarcodeToOrder((int)$id_order, $barcode);
                            }
                        }
                    }
                }
            }
        }
        //Generate Barcodes by orders -> MPS (split)
        foreach ($differenceOrders['mps'] as $id_delivery => $typed_orders) {
            $total_num_of_parcels = 0;
            foreach ($typed_orders as $id_orders) {
                foreach ($id_orders as $orderId) {
                    $order = Mage::getModel('sales/order')->load($orderId);
                    $total_num_of_parcels += count($order->getAllVisibleItems());
                }
            }

            foreach ($typed_orders as $type_order => $ordersIds) {
                //We need just first another else is same address because it is a MPS
                $firstOrderId = $typed_orders[$type_order][0];

                $order = Mage::getModel('sales/order')->load($firstOrderId);
                $carrier = Mage::getModel('dpd/carrier');
                $method = $carrier->getMethod($order->getShippingMethod());
                $carrierMethod = $carrier->getModelClass($method);
                $returnDetails = $carrierMethod->returnDetails($ordersIds, $action);
                if (!is_array($returnDetails)) {
                    $this->registerError(__('Lost some data to format DataSend array'));
                    return false;
                }

                $apiReturn = $api->postData($returnDetails); //Get data form DPD API
                if ($apiReturn === false) {
                    foreach ($api->getErrorMessages() as $errorMessage) {
                        $this->registerError($errorMessage);
                        foreach ($typed_orders as $id_orders) {
                            foreach ($id_orders as $id_order) {
                                Mage::getModel('dpd/carrier')->addMessageToOrder((int)$id_order, $errorMessage, 'DPD ERROR: ');
                            }
                        }
                    }
                    return false;
                }
                if ($apiReturn->status == 'ok') {
                    if (!empty($apiReturn->errlog)) {
                        $this->registerWarning($apiReturn->errlog);
                    }

                    $barcodes = (array)$apiReturn->pl_number;
                    if ($total_num_of_parcels == count($barcodes)) { //if i got correct quantity barcodes
                        $i = 0;
                        foreach ($typed_orders as $id_orders) {
                            foreach ($id_orders as $id_order) {
                                $order = Mage::getModel('sales/order')->load($id_order);
                                foreach ($order->getAllVisibleItems() as $product) {
                                    Mage::getModel('dpd/carrier')->setBarcodeToOrder((int)$id_order, $barcodes[$i++]);
                                }
                            }
                        }
                    } else {
                        foreach ($apiReturn->pl_number as $barcode) {
                            foreach ($typed_orders as $id_orders) {
                                foreach ($id_orders as $id_order) {
                                    Mage::getModel('dpd/carrier')->setBarcodeToOrder((int)$id_order, $barcode);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Group orders who not have Barcodes
     * This is need for MPS method
     *
     * @param  array - Order ids who need group
     * @return array - Multidimensional array
     *         array(
     *             'single' => array([orders]), //Single order not grouping
     *             'multi' => array(
     *                  'groups'=> array([order])), //Grouped orders
     *             'given' => array([orders]), //order list who already have barcode in messages
     *         )
     */
    public function groupOrders($id_orders = array(), $action)
    {
        if (!is_array($id_orders)) {//if not array set
            return false;
        }
        //MPS grouping data
        // MPS available when:
        //  Same Shipping Address;
        //  Same Package Type;
        //  Same Delivery Day;
        //  Order Delivery NOT to ParcelStore

        $groupedOrders = array(
            'single' => array(),
            'given' => array(),
            'multi' => array(),
            'mps' => array()
        );

        $orders = array();
        if (!count($id_orders)) {//if orders id is empty
            return $groupedOrders; //return empty array
        }

        foreach ($id_orders as $id_order)
        {
            if (Mage::getModel('dpd/carrier')->hasBarcode($id_order)) {//This order already has barcode?
                $groupedOrders['given'][] = $id_order;
                continue;
            }
            $orderData = Mage::getModel('sales/order')->load($id_order);
            $customerAddressId = $orderData->getShippingAddress()->getCustomerAddressId();
            if (empty($customerAddressId)) {
                $customerAddressId = $orderData->getBillingAddress()->getCustomerAddressId();
            }
            if (empty($customerAddressId)) {
                $customerAddressId = $orderData->getShippingAddressId();
            }

            $orders[$customerAddressId][$this->getParcelType($id_order)][] = array(
                'order' => $id_order,
                'shipping_address' => $customerAddressId, //same shipping address
                'shipping_type' => $this->getParcelType($id_order), //same shipping type
                //'delivery_day' => '',
            );
        }

        if ($action == 'split') {//We need to split order by item to create MPS
            foreach ($orders as $customer_address_id => $data_delivery) {
                foreach ($data_delivery as $type_order => $data_order) {
                    foreach ($data_order as $order) {
                        if ($type_order == 'PS') {
                            $groupedOrders['single'][$customer_address_id][$type_order][] = $order['order'];
                        } else {
                            $groupedOrders['mps'][$customer_address_id][$type_order][] = $order['order'];
                        }
                    }
                }
            }
        }

        if ($action == 'join') {//We need to join order to create one label
            foreach ($orders as $customer_address_id => $data_delivery) {
                foreach ($data_delivery as $type_order => $data_order) {
                    if (count($data_order) > 1) {
                        foreach ($data_order as $order) {
                            if ($type_order == 'PS') {
                                $groupedOrders['single'][$customer_address_id][$type_order][] = $order['order'];
                            } else {
                                $groupedOrders['multi'][$customer_address_id][$type_order][] = $order['order'];
                            }
                        }
                    } else {
                        $groupedOrders['single'][$customer_address_id][$type_order][] = $data_order[0]['order'];
                    }
                }
            }
        }

        return $groupedOrders;
    }

    /**
     * Return DPD shipping type
     *
     * @param  string - Order id
     * @return string - DPD Shipping type, PS - ParcelStore, B2C - Business To Customer, D-COD-B2C...
     * for more info view in $this->$dpd_available_parcel_types
     */
    public function getParcelType($id_order)
    {
        $order = Mage::getModel('sales/order')->load($id_order);
        $carrier = Mage::getModel('dpd/carrier');
        $method = $carrier->getMethod($order->getShippingMethod());
        $carrierMethod = $carrier->getModelClass($method);
        $parcelType = $carrierMethod->getType($id_order);
        if ($parcelType !== false) {
            return $parcelType;
        }

        //Something wrong
        $this->registerWarning(__('Order: ').$id_order.', '.__('something is wrong, cant find Parcel Type.'));
        return false;
    }

    /**
     * Redirect to page from where come
     */
    private function goBack()
    {
        $this->_redirectReferer();
    }
}
