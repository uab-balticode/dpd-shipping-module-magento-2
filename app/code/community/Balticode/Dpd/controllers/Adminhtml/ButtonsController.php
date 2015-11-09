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

class Balticode_Dpd_Adminhtml_ButtonsController
    extends Mage_Adminhtml_Controller_Action
{
    public function ExportAction()
    {
        $elementName = $this->getRequest()->getParam('element');
        $fileName = $this->getRequest()->getParam('fileName');
        $scope = $this->getRequest()->getParam('scope');
        $scope_id = $this->getRequest()->getParam('scope_id');
        $elementFolder = Mage::getModel('dpd/data')->camelize($elementName);
        Mage::getModel('dpd/'.$elementFolder.'_'.$fileName)->run($scope_id);
    }
}
