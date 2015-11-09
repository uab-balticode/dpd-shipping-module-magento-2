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

class Balticode_Dpd_Model_Deliveryprice
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
     * Init `balticode_dpd_delivery_price` table
     */
    protected function _construct()
    {
        $this->_init('dpd/deliveryprice');
    }

    /**
     * Function add data to database about ParcelStore
     *
     * @param array $data ParcelStore Data
     * @return  boolean
     */
    public function addRestriction($data)
    {
        $this->validateRestriction($data);
        if (count($this->getErrorMessages(false))) {
            return false;
        }
        return $this->getResource()->addRow($data);
    }

    /**
     *  Function Deleting value from database
     */
    public function deleteRestrictions($carrier_id, $store_id)
    {
        $write = $this->getResource()->getWriteConnection();
        $condition = join(' AND ', array(
            $write->quoteInto('id_shop=?', $store_id),
            $write->quoteInto('carrier_id=?', $carrier_id)
          ));
        return $this->getResource()->removeRows($condition);
    }

    /**
     * Return Restriction data by Restriction id
     *
     * @param  string | int $restriction_id id_dpd_delivery_price
     * @param  [type] $other         [description]
     * @return [type]                [description]
     */
    public function getRestriction($restriction_id, $other = null)
    {
        return $this->load($restriction_id);
    }

    /**
     * Existing post codes for this conditions 
     * 
     * @param  int  $post_code  post code
     * @param  string  $carrier_id carrier name "carrier" or "ps"
     * @param  int | string  $id_shop    selected store id
     * @return boolean
     */
    public function isExistPostCode($post_code, $carrier_id, $id_shop = null)
    {
        if (count($this->collectRestriction($post_code, $carrier_id, $id_shop))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return Restriction list by filtred fields
     */
    public function collectRestriction($post_code = false, $carrier_id = false, $id_shop = false)
    {
        $collection = $this->getCollection();
        $collection->addFieldToSelect('*');

        if ($post_code) {
            $collection->addFieldToFilter('postcode',$post_code);
        }

        if ($carrier_id) {
            $collection->addFieldToFilter('carrier_id',$carrier_id);
        }

        if ($id_shop !== '*') {
            if ($id_shop === false) {
                $id_shop = Mage::app()
                    ->getWebsite()
                    ->getDefaultGroup()
                    ->getDefaultStoreId();
            }
            $collection->addFieldToFilter( //Select
                array('id_shop', 'id_shop'), //id_shop eq OR id_shop eq
                array($id_shop, '0') // eq to $id_shop or eq to 0 -> Default store id
            );
        }

        return $collection->getData();
    }

    /**
     * Validating data
     * If required data lost registred error
     * If something is wrong correcting data
     *
     * @param  array &$data [description]
     * @return [type]        [description]
     */
    public function validateRestriction(&$data)
    {
        if (!is_array($data)) {
            $this->setErrorMessage('ERROR: method -> '.__METHOD__.'.
                Reason -> data is not array. Data type:'.gettype($data));
            return false;
        }

        /* Validating post code */
        if (empty($data['postcode'])) {//ParcelStore ID is not set
            $this->setErrorMessage('ERROR: method -> '.__METHOD__.'.
                Reason -> postcode is not set. Data given: '.serialize($data));
        }

        /* Validating carrier id */
        if (empty($data['carrier_id'])) {//ParcelStore ID is not set
            $this->setErrorMessage('ERROR: method -> '.__METHOD__.'.
                Reason -> carrier_id is not set. Data given: '.serialize($data));
        }

        /* Validating store id */
        if (!isset($data['id_shop'])) {//if store id not set
            $this->setWarningMessage('WARNING: method -> '.__METHOD__.'.
                Reason -> id_shop is not correct. Data given: '.serialize($data));
            $data['id_shop'] = Mage::app()->getRequest()->getParam('section'); //set store id
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
        if ($clear) {
            $this->error_messages = array();
        }
        return $messages;
    }

    /**
     * Use delivery price by postcode
     * 
     * @return boolean
     */
    public function isEnabled()
    {
        return (boolean)Mage::helper('dpd/data')->getConfigData('carrier_price_pcode');
    }
}
