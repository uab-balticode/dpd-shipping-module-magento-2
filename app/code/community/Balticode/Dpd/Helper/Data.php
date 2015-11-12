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

class Balticode_Dpd_Helper_Data extends Mage_Core_Helper_Abstract
{
    public $module = 'dpd';
    private $log_file_name = 'BalticodeDpdSystemReports.log';

    /**
     * CSV string convert to array
     * 
     * @param  string $csv_string CSV file
     * @param  string $separator  columns separator
     * @param  string $enclosure    field enclosure
     * @return array             multidimensional array of csv
     */
    public function csvToArray($csv_string = '', $separator = ",", $enclosure = '"')
    {
        if (!is_string($csv_string)) {
            return false;
        }
        $lines = explode("\n", str_replace(array(chr(10),"\r"), "\n", $csv_string));
        foreach (array_filter($lines) as $line_nr => $line) {
            foreach (explode($separator, $line) as $value) {
                $array[$line_nr][] = trim($value, $enclosure); 
            }
        }
        return $array;
    }

    /**
     * Array convert to CSV string
     * @param  array  $array     Data
     * @param  string $separator parameter sets the field delimiter (one character only). 
     * @param  string $enclosure   parameter sets the field enclosure (one character only). 
     * @return string            CSV value
     */
    public function arrayToCsv($array = array(), $separator = ',', $enclosure = '"')
    {
        if (!is_array($array)) {
            return false;
        }
        $csv = fopen('php://temp/maxmemory:'. (5*1024*1024), 'r+');

        foreach ($array as $line) {
            fputcsv($csv, $line, $separator, $enclosure);
        }
        rewind($csv);
        $output = stream_get_contents($csv);
        return $output;
    }

    /**
     * Return time of current time in Europe/Vilnius time zone
     * 
     * @param  string $format return time format
     * @return string         current time
     */
    public function now($format = 'Y-m-d H:i')
    {
        date_default_timezone_set("Europe/Vilnius");
        return date($format);
    }

    /**
     * Put messages to log file is allowed by module settings
     * 
     * @param  string $message Some message
     * @return [type]          [description]
     */
    public function log($message, $fileName = '')
    {
        if ($fileName === '') {
            $fileName = $this->log_file_name;
        }

        if ($this->getConfigData('log')) {
            Mage::log($message, null, $fileName);
        }
        return __CLASS__;
    }

    /**
     * Retrieve information from settings configuration
     *
     * @param   string $field
     * @return  mixed
     */
    public function getConfigData($field, $store_id = null, $block = null)
    {
        if ($block === null) {
            $block = $this->module;
        }

        $path = 'carriers/'.$block.'/'.$field;
        return Mage::getStoreConfig($path, $store_id);
    }

    /**
     * Return PaymentMethod code of order Id
     * 
     * @param  string $orderId Order id
     * @return string          Payment Method code
     */
    public function getPaymentCode($orderId)
    {
        $order = Mage::getModel('sales/order')->load($orderId);
        return $order->getPayment()->getMethodInstance()->getCode();
    }

    /**
     * Currency converter
     * 
     * @param  float $price
     * @param  string $from  Currency code
     * @param  string $to    Currency code
     * @return string        Converter currency value
     */
    public function convertCurrency($price, $currentCurrencyCode = null, $baseCurrencyCode = 'EUR')
    {
        if (is_null($currentCurrencyCode)) {
            $currentCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
        }
        return Mage::helper('directory')->currencyConvert($price, $currentCurrencyCode, $baseCurrencyCode);
    }

    /**
     * Get Current Store ID
     * @return int Store id
     */
    public function getStoreId()
    {
        if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getStore())) { // store level
            $store_id = Mage::getModel('core/store')->load($code)->getId();
        } elseif (strlen($code = Mage::getSingleton('adminhtml/config_data')->getWebsite())) { // website level
            $website_id = Mage::getModel('core/website')->load($code)->getId();
            $store_id = Mage::app()->getWebsite($website_id)->getDefaultStore()->getId();
        } else {// default level
            $store_id = 0;
        }
        return (int)$store_id;
    }
}
