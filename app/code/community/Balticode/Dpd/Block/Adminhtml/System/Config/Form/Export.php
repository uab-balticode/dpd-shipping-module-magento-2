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

class Balticode_Dpd_Block_Adminhtml_System_Config_Form_Export
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $elementId = $element->getHtmlId();
        preg_match("/[^_\W]+$/", $elementId, $fileName); //Grab last word

        $moduleName = Mage::helper('dpd/data')->module; //getModule name
        $newElement = str_replace($moduleName, "", $elementId); //remove module name from string
        $newElement = str_replace($fileName[0], "", $newElement); //remove file name from string
        $elementName = trim($newElement,"_");
        $url = Mage::helper("adminhtml")->getUrl(
            "dpd/adminhtml_buttons/export",
            array(
                'element'=> $elementName,
                'fileName' => $fileName[0],
                'scope' => $this->getForm()->getScope(),
                'scope_id' => $this->getForm()->getScopeId()
            )
        );

        $label = $element->getFieldConfig()->button_label->__toString();
        if (empty($label)) {
            $label = trim($element->getLabel());
        }

        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setId($elementId)
                    ->setType($element->gettype())
                    ->setLabel(__($label))
                    ->setOnClick("setLocation('$url')")
                    ->toHtml();

        return $html;
    }
}
