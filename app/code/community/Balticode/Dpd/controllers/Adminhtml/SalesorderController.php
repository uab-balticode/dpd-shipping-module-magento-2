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

class Balticode_Dpd_Adminhtml_SalesorderController
    extends Mage_Adminhtml_Controller_Action
{
    /**
     * Save Additional data about shipping method
     * Class Method using from AdminHtml when editing Order and set data from dropDown
     * This Class is run from JS
     */
    public function SaveAction()
    {
        $quoteId = $_POST['quoteId'];
        $params = Mage::getSingleton('dpd/data')
                ->objectToArray(json_decode($_POST['option']));
        $quote = Mage::getModel('sales/quote')->loadByIdWithoutStore($quoteId);
        $quote->setDpdDeliveryOptions(serialize($params));
        $quote->save();
    }
}
