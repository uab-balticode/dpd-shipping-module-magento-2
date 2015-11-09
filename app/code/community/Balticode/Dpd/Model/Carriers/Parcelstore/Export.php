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

class Balticode_Dpd_Model_Carriers_Parcelstore_Export
    extends Mage_Core_Model_Config_Data
{
    private $fileName = '';
    private $fileExtends = 'csv';
    private $fileHeader = array();
    private $fileContent = array();

    /**
     * Master function to run export
     * @return
     */
    public function run()
    {
        $this->collectData(); //Collecting data to export
        $this->intersectData(); //Leave only who we need
        $this->addHeader($this->fileContent, $this->fileHeader, $this->fileContent);
        $csv = Mage::helper('dpd/data')->arrayToCsv($this->fileContent);
        Mage::getSingleton('dpd/data')->makeDownload($this->fileName, $csv, 'text/csv');
    }

    public function collectData()
    {
        $this->fileName = Mage::getSingleton('dpd/carriers_parcelstore_parcelstore')->key
            .'.'
            .$this->fileExtends;
        $this->fileHeader = Mage::getSingleton('dpd/carrier')->csvHeader;

        $this->fileContent = Mage::getModel('dpd/deliveryprice')->collectRestriction(
            false,
            Mage::getSingleton('dpd/carriers_parcelstore_parcelstore')->key,
            Mage::helper('dpd/data')->getStoreId());
    }

    private function intersectData()
    {
        $fileContent = array();
        foreach ($this->fileContent as $key => $headers) {
            $fileContent[$key] = array_intersect_key($headers, array_flip($this->fileHeader));
        }
        $this->fileContent = $fileContent;
    }

    /**
     * Add Header to array,
     * This simple way to add some data to array begin
     *
     * @param   array $base_array some base array
     * @param   mix $header some data who need to add in array
     * @param   array $target if set variable result set for them if not result return
     * @return  array with added header
     */
    private function addHeader($base_array = array(), $header = array(), &$target = false)
    {
        if ($target !== false && !is_array($target)) {
            $target = false;
        }

        $array = array_reverse($base_array);
        $array[] = $this->fileHeader; //Add header
        $array = array_reverse($array);
        if ($target === false) {
            return $array;
        } else {
            $target = $array;
        }
        unset($array);
    }
}
