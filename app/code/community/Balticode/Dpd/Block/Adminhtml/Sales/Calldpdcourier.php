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

class Balticode_Dpd_Block_Adminhtml_Sales_Calldpdcourier
    extends Mage_Adminhtml_Block_Template
{
    /**
     * constructor load "Call DPD Courier" confirm window in Sales -> Order list
     */
    public function _construct()
    {
        $this->setTemplate('dpd/sales/calldpdcourier.phtml');
        parent::_construct();
    }

    /**
     * Get URL to controller where send data to DPD API
     * @return [type] [description]
     */
    public function getControllerUrl()
    {
        return Mage::helper("adminhtml")->getUrl("dpd/adminhtml_carrier/call/");
    }
}
