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

class Balticode_Dpd_Model_Adminhtml_System_Config_Form_Serialized
    extends Mage_Core_Model_Config_Data
{
    /**
     * Auto load function after load data in Backend
     */
    protected function _afterLoad()
    {
        if (!is_array($this->getValue())) {
            $value = $this->getValue();
            $this->setValue(empty($value) ? false : unserialize($value));
        }
    }

    /**
     * Auto load function of before saving data
     * Test value key before saving if key is -1 (this is from template)
     * Data is not need to use to us so skip them
     */
    protected function _beforeSave()
    {
        $thisValue = $this->getValue();
        if (is_array($thisValue))
        {
            foreach ($thisValue as $key => $value)
                unset($thisValue[$key]['-1']);
            $this->setValue(serialize($thisValue));
        }
    }
}
