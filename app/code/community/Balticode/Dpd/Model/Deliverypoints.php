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

class Balticode_Dpd_Model_Deliverypoints
    extends Mage_Core_Model_Abstract
{
    /**
     * Array of error messages
     * 
     * @var array
     */
    protected $error_messages = array();

    /**
     * Array of Warning messages
     * 
     * @var array
     */
    protected $warning_messages = array();

    /**
     * Init `balticode_dpd_delivery_point` table
     */
    protected function _construct()
    {
        $this->_init('dpd/deliverypoints');
    }

    /**
     * Function add data to database about ParcelStore
     * 
     * @param array $data ParcelStore Data
     * @return  boolean
     */
    public function addParcelStore($data)
    {
        $this->validateParcelStore($data);
        if (count($this->getErrorMessages(false))) {
            return false;
        }

        // $data = array(
        //         'parcelshop_id' => '9876213',
        //         'company' => 'Company',
        //         'city' => 'City',
        //         'pcode' => '86302',
        //         'street' => 'Gamyklos',
        //         'country' => 'Lt',
        //         'email' => 'mano@naujas.com',
        //         'phone' => '+370612555',
        //         'comment' => 'Siuntin rasi namie',
        //         'created_time' => time(),
        //         'update_time' => time(),
        //         'active' => 1,
        //         'deleted' => 0,
        //     );
        return $this->getResource()->addRow($data);
    }

    /**
     * Collect ParcelStores and put in database, before truncate the parcelStore table
     * 
     * @param  boolean $country [description]
     * @param  boolean $city    [description]
     * @return mix           [description]
     */
    public function generateDeliveryPoints($country = false, $city = false)
    {
        $response = true;
        $api = Mage::helper('dpd/api');

        foreach ($api->getWarningMessages() as $message) {
            Mage::getSingleton('core/session')->addWarning($message);
        }

        if (count($api->getErrorMessages(false))) {
            foreach ($api->getErrorMessages() as $message) {
                Mage::getSingleton('core/session')->addError($message);
            }
            return false;
        }

        $deliveryPointsObj = $api->getDeliveryPoints($country, $city);
        $deliverypoints = Mage::getModel('dpd/data')->objectToArray($deliveryPointsObj);

        if (!isset($deliverypoints['errlog'])) {
            $this->getResource()->truncate();

            foreach ($deliverypoints as $deliverypoint) {
                $deliverypoint['created_time'] = date('Y-m-d H:i:s');
                $deliverypoint['update_time'] = date('Y-m-d H:i:s');
                try {
                    $this->addParcelStore($deliverypoint);
                }
                catch (Exception $e) {
                    $response = false;
                    Mage::helper('dpd/data')->log($e);
                }
            }
        } else {
            $response = $deliverypoints;
        }

        return $response;
    }

    /**
     * Return ParcelStore data by parcelStore_id
     * 
     * @param  string | int $parcelshop_id
     * @param  [type] $other         [description]
     * @return [type]                [description]
     */
    public function getParcelStore($parcelshop_id, $other = null)
    {
        return $this->load($parcelshop_id);
    }

    /**
     * Return ParcelStore list by filtred fields
     * 
     * @param  string | boolean $country       name of country LT / LV / EE
     * @param  string | boolean $city          name of city
     * @param  string | boolean $parcelshop_id parcelshop identification number
     * @param  string  $active        activated parcel store
     * @param  string  $deleted       not deletes parcel store
     * @return array                  Parcel store description
     */
    public function collectDeliveryPoints($country = false, $city = false, $parcelshop_id = false, $active = '1', $deleted = '0')
    {
        $collection = $this->getCollection();
        $collection->addFieldToSelect('*');

        if ($country) {
            $collection->addFieldToFilter('country',$country);
        }

        if ($city) {
            $collection->addFieldToFilter('city',$city);
        }

        if ($parcelshop_id) {
            $collection->addFieldToFilter('parcelshop_id',$parcelshop_id);
        }

        if ($active) {
            $collection->addFieldToFilter('active',$active);
        }

        if ($deleted) {
            $collection->addFieldToFilter('deleted',$deleted);
        }

        return $collection->getData();
    }

    /**
     * Return ParcelStore list by filtred fileds
     * GROUPED BY CITY
     * 
     * @param  string | boolean $country       name of country LT / LV / EE
     * @param  string | boolean $city          name of city
     * @param  string | boolean $parcelshop_id parcelshop identification number
     * @param  string  $active        activated parcel store
     * @param  string  $deleted       not deletes parcel store
     * @return array                  Parcel store description
     */
    public function getDeliveryPoints($country = false, $city = false, $parcelshop_id = false, $active = '1', $deleted = '0')
    {
        $deliveryPointsGroup = array();
        $deliveryPointsMess = $this->collectDeliveryPoints($country, $city, $parcelshop_id, $active, $deleted);
        foreach ($deliveryPointsMess as $company) {
            $deliveryPointsGroup[trim($company['city'])][] = $company;
        }

        return $deliveryPointsGroup;
    }

    /**
     * ParcelStore Sort by name and by priorityCity
     * 
     * @param  [type] &$deliveryPoints [description]
     * @param  array  $priorityCity    [description]
     * @return [type]                  [description]
     */
    public function sortDeliveryPoints(&$deliveryPoints, $priorityCity = array())
    {
        ksort($deliveryPoints);
        ksort($priorityCity);

        $new_array = array();

        foreach ($priorityCity as $city) {
            $cityName = trim($city);
            if (isset($deliveryPoints[$cityName])) {
                $new_array[$cityName] = $deliveryPoints[$cityName];
                unset($deliveryPoints[$cityName]);
            }
        }
        $deliveryPoints = $new_array + $deliveryPoints;
    }

    /**
     * Validating data
     * If required data lost registred error
     * If something is wrong correcting data
     * 
     * @param  array &$data [description]
     * @return [type]        [description]
     */
    public function validateParcelStore(&$data)
    {
        if (!is_array($data)) {
            $this->setErrorMessage('ERROR: method -> '.__METHOD__.'.
                Reason -> data is not array. Data type:'.gettype($data));
            return false;
        }

        $current_time = Mage::helper('dpd/data')->now('Y-m-d H:i:s');

        /* Validating update time */
        if (!isset($data['update_time'])) {//If not set created time
            $data['update_time'] = $current_time; //set created time
        }

        if (strtotime($data['update_time']) > strtotime($current_time)) {//if created time is future
            $this->setWarningMessage('WARNING: method -> '.__METHOD__.'.
                Reason -> update_time is not correct. Data given: '.serialize($data));
            $data['update_time'] = $current_time; //set created time to current
        }

        /* Validating create time */
        if (!isset($data['created_time'])) {//If not set created time
            $data['created_time'] = $current_time; //set created time
        }

        if (strtotime($data['created_time']) > strtotime($current_time)) {//if created time is future
            $this->setWarningMessage('WARNING: method -> '.__METHOD__.'.
                Reason -> create_time is not correct. Data given: '.serialize($data));
            $data['created_time'] = $current_time; //set created time to current
        }

        /* Validating pardelshop id */
        if (empty($data['parcelshop_id'])) {//ParcelStore ID is not set
            $this->setErrorMessage('ERROR: method -> '.__METHOD__.'.
                Reason -> parcelshop_id is not set. Data given: '.serialize($data));
        }
        /* validating post code */
        if (empty($data['pcode'])) {
            $this->setErrorMessage('ERROR: method -> '.__METHOD__.'.
                Reason -> pcode is not set. Data given: '.serialize($data));
        } else {
            $data['pcode'] = preg_replace('/\D/', '', $data['pcode']);
        }
    }

    /**
     * Put Message to array like warning
     * 
     * @param string $message some message about warning
     */
    private function setWarningMessage($message)
    {
        if (!is_string($message)) {
            return false;
        }
        $this->warning_messages[] = $messages; //Put message to array
        Mage::helper('dpd/data')->log($messages); //Put same message and in log file
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
        if ($clear) {
            $this->warning_messages = array();
        }
        return $messages;
    }

    /**
     * Put Message to array like error
     * 
     * @param string $message some message about error
     */
    private function setErrorMessage($message)
    {
        if (!is_string($message)) {
            return false;
        }
        $this->error_messages[] = $messages; //Put message to array
        Mage::helper('dpd/data')->log($messages); //Put same message and in log file
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
        if ($clear) {
            $this->error_messages = array();
        }
        return $messages;
    }
}
