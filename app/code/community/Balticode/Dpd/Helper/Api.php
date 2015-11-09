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

class Balticode_Dpd_Helper_Api
    extends Mage_Core_Helper_Abstract
{
    private $api_name; // API User Name
    private $api_pass; // API User Password
    private $id; // API id
    private $api_url; // API URL
    public $warning_messages = array();
    public $error_messages = array();

    /**
     * Constructor collect login data from backend settings
     */
    public function __construct()
    {
        $this->api_name = Mage::helper('dpd/data')->getConfigData('username');
        $this->api_pass = Mage::helper('dpd/data')->getConfigData('password');
        $this->id = Mage::helper('dpd/data')->getConfigData('id');
        $this->api_url = Mage::helper('dpd/data')->getConfigData('api');
    }

    /**
     * Ask API about available parcelStore list
     * Available filter of country or city
     *
     * @param  boolean | string $country if boolean - false filter is not enabled
     *                                   if String - filter value
     * @param  boolean | string $city    if boolean - false filter is not enabled
     *                                   if String - filter value
     * @return Array           PacelStore list
     */
    public function getDeliveryPoints($country = false, $city = false)
    {
        $data = json_decode($this->getResource());
        if ($data->status == 'ok')
        {
            $all_points = $data->parcelshops;
            $correct_points = $this->getFiltredPoints($all_points, $country, $city);
            return $correct_points;
        }
        else
        {
            $this->setErrorMessage($data->errlog);
            return $data;
        }
    }

    /**
     * Parcel Store filter
     *
     * @param  array $all_points All Available ParcelStore points collected from API
     * @param  boolean | string $country    filter attribute
     * @param  boolean | string $city       filter attribute
     * @return Array             return Available ParcelStore filtreted by attributes
     */
    private function getFiltredPoints($all_points, $country, $city)
    {
        if ($country)
        {
            $this->setFiltringCountry($country);
            $all_points = array_filter($all_points, array($this, 'filterByCountry'));
        }
        if ($city)
        {
            $this->setFiltringCity($city);
            $all_points = array_filter($all_points, array($this, 'filterByCity'));
        }
        return $all_points;
    }

    /**
     * Send parameters to DPD API about who we need from them
     * If return value is not correct register like some error
     *
     * @param  array  $params Some params about who need from API
     * @param  string $url    Url from where need to get params
     *                        by default is not need to set - Grab from backend settings
     * @return mix            some Parameters returned from DPD API
     */
    private function getResource($params = array('action' => 'parcelshop_info'), $url = null)
    {
        $url_link = (($url === null)?$this->api_url:$url);
        $params['username'] = $this->api_name;
        $params['password'] = $this->api_pass;
        $api = $url_link.$params['action'].'.php';
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($params),
                'timeout' => 10,
        ));
        $context = stream_context_create($options);
        $contents = file_get_contents($api, false, $context);

        if ($contents === false)
        {
            $message = array('status' => 'err','errlog' => 'Wrong URL: '.$api);
            $contents = json_encode($message);
        }
        return $contents;
    }

    /**
     * Send collected parameters to DPD API
     *
     * @param  array    parameters
     * @return mix      some returned result from API
     */
    public function postData($parameters)
    {
        $response = null;
        $response = $this->getResource($parameters);
        if (self::is_pdf($response)) //Is pdf file content?
            return $response;

        if (is_string($response) && self::is_Json($response)) //Is string of jSon?
            $response = json_decode($response); //Convert to Object

        if (is_object($response)) //Is object?
            if ($response->status !== 'ok') //Is status ok?
            {
                $this->setErrorMessage($response->errlog);
                return false;
            }

        return $response;
    }

    /**
     * test is PDF content
     * 
     * @param  string  $fileContent PDF content
     * @return boolean
     */
    public function is_pdf($fileContent)
    {
        if (is_string($fileContent))
        {
            $triggers = chr(37).chr(80).chr(68).chr(70).chr(45); //  %PDF-
            $heder = substr($fileContent, 0, strlen($triggers));
            if ($heder == $triggers)
                return true;
            else
                return false;
        }
        return false;
    }

    /**
     * test is JSON type string content
     * 
     * @param  string  $string - some string
     * @return boolean
     */
    public function is_Json($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE); //It is no errors?
    }

    /**
     * Set filter about country
     * 
     * @param string    country name
     * @return Object   this class
     */
    public function setFiltringCountry($country)
    {
        $this->filtring_country = $country;
        return __CLASS__;
    }

    /**
     * Set filter about city
     * 
     * @param string    city name
     * @return Object   this class
     */
    public function setFiltringCity($city)
    {
        $this->filtring_city = $city;
        return __CLASS__;
    }

    private function filterByCountry($obj)
    {
        return ($obj->country == $this->filtring_country)? true : false;
    }

    private function filterByCity($obj)
    {
        return ($obj->city == $this->filtring_city)? true : false;
    }

    /**
     * Put Message to array like warning
     * 
     * @param string $message some message about warning
     */
    private function setWarningMessage($message)
    {
        if (!is_string($message))
            return false;
        $this->warning_messages[] = $message; //Put message to array
        Mage::helper('dpd/data')->log($message); //Put same message and in log file
    }

    /**
     * Return array of warning messages
     * 
     * @param  boolean $clear Do clean array after read all messages?
     * @return array of warnings
     */
    public function getWarningMessages($clear = true)
    {
        $messages = $this->warning_messages;
        if ($clear)
            $this->warning_messages = array();
        return $messages;
    }

    /**
     * Put Message to array like error
     * 
     * @param string $message some message about error
     */
    private function setErrorMessage($message)
    {
        if (!is_string($message))
            return false;
        $this->error_messages[] = $message; //Put message to array
        Mage::helper('dpd/data')->log($message); //Put same message and in log file
    }

    /**
     * Return array of error messages
     * 
     * @param  boolean $clear Do clean array after read all messages?
     * @return array of errors
     */
    public function getErrorMessages($clear = true)
    {
        $messages = $this->error_messages;
        if ($clear)
            $this->error_messages = array();
        return $messages;
    }
}
