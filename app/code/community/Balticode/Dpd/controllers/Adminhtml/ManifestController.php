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

class Balticode_Dpd_Adminhtml_ManifestController extends Mage_Adminhtml_Controller_Action
{
    public function ManifestAction($orders = array())
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

        if ($availableOrders === false) { //If something wrong return false
            return false;
        }

        $this->sortOrders($availableOrders);
        $this->processParcelDataSend();
        if (0) { //This is for future
            $pdfContent = $this->getManifestFromAPI($availableOrders);
        } else {
            $pdfContent = $this->generateManifest($availableOrders); //Get Manifest PDF content
        }

    }

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
     * Say to DPD WebLabel API about printing Manifest to need process current parcels
     * 
     * @return boolean
     */
    private function processParcelDataSend()
    {
        $parameters = array(
            'action' => 'parcel_datasend',
        );
        $api = Mage::helper('dpd/api');
        $apiReturn = $api->postData($parameters);
        if ($apiReturn === false) {
            foreach ($api->getErrorMessage() as $errorMessage) {
                $this->registerError($errorMessage);
            }
            return false;
        }
    }

    /**
     * This function not full complete because DPD API not have full manifest report
     * 
     * @param  array $availableOrders id_orders
     * @return pdf content from DPD API
     */
    private function getManifestFromAPI($availableOrders)
    {
        $availableOrders;
        $this->registerError('If you reality know what you doing so why you leave this message?');
        return false;

        // $parameters = array(
        //     'action' => 'parcel_manifest_print',
        //     'type' => 'manifest', // manifest, manifest_cod, summary_list
        //     'date' => date('Y-m-d'),
        // );

        // $api = Mage::helper('dpd/api');
        // $pdfContent = $api->postData($parameters);
        // return $pdfContent;
    }

    // /**
    //  * Save file content to temp folder to not lost data
    //  * @param  string $fileConent some file content who need to be saved
    //  * @return string full path to file with file name
    //  */
    // private function saveFileContent($fileConent)
    // {
    //     $pathToFile = _PS_PDF_DIR_.'dpd_'.time().'_label';
    //     $writedBytes = file_put_contents($pathToFile, $fileConent);
    //     if ($writedBytes === false)
    //     {
    //         $this->registerError('Error do not have permission to write to this folder: '._PS_PDF_DIR_);
    //         return false;
    //     }
    //     return $pathToFile;
    // }

    /**
     * Return generated Manifest file name
     * 
     * @param  array  $parameters some order parameters is for future
     * @return string Label file name
     */
    private function getManifestFileName()
    {
        $string = 'Manifest-';
        $time = date('Ymd_H-i', time());
        return $string.$time.'.pdf';
    }

    /**
     * Return Manifest PDF content
     * 
     * @param  array  $availableOrders - Orders id
     * @return string - Manifest content
     */
    private function generateManifest($availableOrders = array())
    {
        require_once(str_replace('\\','/',Mage::getBaseDir().'/lib/dompdf/dompdf_config.inc.php'));
        
        $htmlContent = $this->getLayout()
                        ->createBlock('dpd/adminhtml_sales_order_manifest', 'dpd_manifest')
                        ->setOrders($availableOrders)
                        ->toHtml();

        if (false) { //Preview If: true - Manifest show in internet browser; false - stream content to pdf
            echo $htmlContent;
        } else {
            $dompdf = new DOMPDF();
            $dompdf->load_html($htmlContent,"UTF-8");
            $dompdf->set_paper("A4", 'portrait');
            $dompdf->render();
            $dompdf->stream($this->getManifestFileName());
        }
    }

    /**
     * Sorder orders
     * 
     * @param  pointer array &$availableOrders source array
     * @param  int array argument $direction - SORT_ASC to sort ascendingly or SORT_DESC to sort descendingly
     * @return none, sortered array is set to pointer
     */
    private function sortOrders(&$availableOrders, $direction = SORT_ASC)
    {
        if (!is_array($availableOrders)) {
            return false;
        }

        $sort_col = array();
        foreach ($availableOrders as $key => $id_order) {
            $sort_col[$key] = Mage::getModel('dpd/carrier')->getBarcodeFromOrder($id_order);
        }

        array_multisort($sort_col, $direction, $availableOrders);
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
     * Return available currier to get some info for e.g. Labels
     * 
     * Order currier method is a DPD method?
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
     * Redirect to page from where come
     */
    private function goBack()
    {
        $this->_redirectReferer();
    }
}
