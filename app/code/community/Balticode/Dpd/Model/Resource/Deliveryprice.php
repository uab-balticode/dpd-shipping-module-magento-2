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

class Balticode_Dpd_Model_Resource_Deliveryprice
    extends Mage_Core_Model_Mysql4_Abstract
{

    public function __construct()
    {
        parent::__construct();
    }

    protected function _construct()
    {
        $this->_init('dpd/deliveryprice', 'id_dpd_delivery_price');
    }

    public function addRow($data)
    {
        $this->_getWriteAdapter()->insert(
            $this->getMainTable(),
            $data
        );
        return $this;
    }

    public function removeRows($condition)
    {
        $this->_getWriteAdapter()->delete(
            $this->getMainTable(),
            $condition
        );
        return $this;
    }

    public function getWriteConnection()
    {
        return $this->_getWriteAdapter();
    }
}
