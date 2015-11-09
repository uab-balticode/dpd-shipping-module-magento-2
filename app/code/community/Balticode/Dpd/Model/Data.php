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

class Balticode_Dpd_Model_Data
    extends Mage_Core_Model_Abstract
{
    /**
     * Convert object to Array
     *
     * @param  object $obj some data
     * @return array      some data
     */
    public function objectToArray($obj)
    {
        if (is_object($obj)) {
            $obj = get_object_vars($obj);
        }

        if (is_array($obj)) {
            return array_map([__CLASS__, __METHOD__], $obj);
        } else {
            return $obj;
        }
    }

    /**
     * Recursive trim and characters to lower from array values
     *
     * @param  array $arr some array data
     * @return array      return same array just trimmed and lowercase
     */
    public function array_change_value_case($arr)
    {
        if (!is_array($arr)) {
            return array();
        }
        return array_map(
            function($item) {
                if (is_array($item)) {
                    return self::array_change_value_case($item);
                } else {
                    return trim(strtolower($item));
                }
            },
        $arr);
    }

    /**
    * Search value in array, if found return key
    *
    * @param string $needle - value of searching string
    * @param array $haystack - full array where searching
    * @return string || boolean (string - key name || boolean - false - if not found)
    */
    public function recursive_array_search($needle, $haystack)
    {
        foreach ($haystack as $key => $value) {
            $current_key = $key;
            if ($needle === $value ||
                (is_array($value) &&
                    self::recursive_array_search($needle, $value) !== false)) {
                return $current_key;
            }
        }
        return false;
    }

    /**
     * Return date and time by stamp given string
     * 
     * @param  string - time, example: 2015/06/25 or 19:55
     * @param  string - format
     * @return string | Boolean if type of variables is not correct
     */
    public function toTimeStamp($time, $time_stamp = 'Y-m-d H:i:s')
    {
        if (!is_string($time)) {
            return false;
        }
        return date(trim($time_stamp), strtotime($time));
    }

    /**
     * Flip DualDimensional Array
     *
     * @param array
     * @return  array
     */
    public function flipArrayList($list)
    {
        $new_array = array();
        if ($list && count($list)) {
            $options = array_keys($list); // get all keys c_name, name, c_post and etc
            $values_count = array_keys($list[$options[0]]); //get how much records available
            foreach ($values_count as $row) { //get rows
                foreach ($options as $option) {
                    $new_array[$row][$option] = $list[$option][$row];
                }
            }
        }
        return $new_array;
    }

    public function makeDownload($fileName, $content, $type = 'text/csv')
    {
        require_once(Mage::getModuleDir('controllers','Balticode_Dpd').DS.'DownloadController.php');
        $controller = new Balticode_Dpd_DownloadController(
                        Mage::app()->getRequest(),
                        Mage::app()->getResponse()
                    );
        $controller->prepareDownloadResponse($fileName, $content, $type);
    }

    /*
    * String covert to Camel Caps
    */
    public function camelize($word)
    {
        return preg_replace('/(^|_)([a-z])/e', 'strtoupper("\\2")', $word);
    }
}
