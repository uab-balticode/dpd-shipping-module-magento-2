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

class Balticode_Dpd_Model_Adminhtml_System_Config_Form_Csvimport
    extends Mage_Core_Model_Config_Data
{
    /**
     * Auto load function after saving data in Backend
     * 
     * @return boolean true, return to make functions continue
     */
    public function _afterSave()
    {
        $files = $this->getFiles($_FILES);
        if (count($files)) {
            foreach ($files as $group_name => $group) {
                foreach ($group as $field_name => $tmp_file) {
                    Mage::getModel('dpd/carrier')->dataImport(
                        Mage::helper('dpd/data')->csvToArray(file_get_contents($tmp_file)),
                        $field_name
                    );
                }
            }
        }
        return true;
    }

    /**
     * Get file name from array of data who is saving in backend
     * 
     * @param  array        saving data array
     * @return string       patch to file
     */
    private function getFiles($data)
    {
        $files = array();
        foreach ($data['groups']['tmp_name'] as $group_name => $group) {
            if ($group_name != 'dpd') {
                continue;
            }
            foreach ($group['fields'] as $field_name => $value) {
                if (!empty($value['value'])) {
                    $files[$group_name][$field_name] = $value['value'];
                }
            }
        }
        return $files;
    }
}
