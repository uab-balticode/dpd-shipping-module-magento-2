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

class Balticode_Dpd_Block_Adminhtml_System_Config_Form_Deliverytime
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected $_addRowButtonHtml = array();
    protected $_removeRowButtonHtml = array();

    protected function _getElementName($name)
    {
        $elementId = $this->getElement()->getId();
        return $elementId.'_'.$name;
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $html = '<div class="grid" >';
        $html .= '<table style="display:none; width:auto;">';
        $html .= '<tbody id="'.$this->_getElementName('template').'">';
        $html .= $this->_getRowTemplateHtml('-1');
        $html .= '</tbody>';
        $html .= '</table>';

        $html .= '<table class="border" cellspacing="0" cellpadding="0" style="width:auto;">';
        $html .= '<thead>';
        $html .= '<tr class="headings" id="heading">';
        $html .= '<th style="width: 100px;">City</th>';
        $html .= '<th style="width: 100px;">Delivery time</th>';
        $html .= '<th style="width: 100px;"></th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody id="'.$this->_getElementName('container').'">';
        $i = 0;

        if ($this->_getValue('city'))
        {
            foreach ($this->_getValue('city') as $i => $f)
            {
                if ($i)
                {
                    $html .= $this->_getRowTemplateHtml($i);
                }
            }
        }
        $html .= '<input id="'.$this->_getElementName('increment').'" type="hidden" value="'.$i.'">';
        $html .= '</tbody>';
        $html .= '<tfoot>';

        $html .= '<tr>';
        $html .= '<td colspan="3" style="text-align:right;">';
        $html .= $this->_getAddRowButtonHtml($this->_getElementName('container'), $this->_getElementName('template'), $this->__('Add combination'));
        $html .= '</td></tr>';
        $html .= '</tfoot>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= $this->_getJs();
        return $html;
    }

    protected function _getJs()
    {
        $html = '<script>';
        $html .= 'function addBlock(template, heading, incrementNumberSelector)';
        $html .= '{';
        $html .= 'var number = document.getElementById(incrementNumberSelector).value;'; //get current value
        $html .= 'number++;'; //incement number
        $html .= 'document.getElementById(incrementNumberSelector).value = number;'; //set value
        $html .= 'var test = $(template).innerHTML;';
        $html .= 'Element.insert($(heading), {bottom: test.replace(/-1/g,number)});';
        $html .= '}';
        $html .= '</script>';
        return $html;
    }

    protected function _getRowTemplateHtml($i = 0)
    {
        $html = '<tr>';
        $html .= '<td style="vertical-align: middle;">';
        $html .= '<input type="text" name="'.$this->getElement()->getName().'[city]['.$i.']" value="'.$this->_getValue('city/'.$i).'" style="width:100px;" />';
        $html .= '</td>';
        $html .= '<td>'.$this->_getDeliveryTime('time',$i).'</td>';
        $html .= '<td style="vertical-align: middle;">'.$this->_getRemoveRowButtonHtml().'</td>';
        $html .= '</tr>';
        return $html;
    }

    protected function _getDisabled()
    {
        return $this->getElement()->getDisabled() ? ' disabled' : '';
    }

    protected function _getValue($key)
    {
        return $this->getElement()->getData('value/'.$key);
    }

    protected function _getAddRowButtonHtml($container, $template, $title='Add')
    {
        if (!isset($this->_addRowButtonHtml[$container]))
        {
            $this->_addRowButtonHtml[$container] = $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setType('button')
                    ->setClass('add '.$this->_getDisabled())
                    ->setLabel($this->__($title))
                    ->setOnClick("addBlock('".$template."', '".$container."', '".$this->_getElementName('increment')."')")
                    ->setDisabled($this->_getDisabled())
                    ->toHtml();
        }
        return $this->_addRowButtonHtml[$container];
    }

    protected function _getRemoveRowButtonHtml($selector='tr', $title='Delete')
    {
        if (!$this->_removeRowButtonHtml)
        {
            $this->_removeRowButtonHtml = $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setType('button')
                    ->setClass('delete v-middle '.$this->_getDisabled())
                    ->setLabel($this->__($title))
                    ->setOnClick("Element.remove($(this).up('".$selector."'))")
                    ->setDisabled($this->_getDisabled())
                    ->toHtml();
        }
        return $this->_removeRowButtonHtml;
    }

    protected function _getDeliveryTime($name, $i)
    {
        $deliveryTime = Mage::getModel('dpd/carriers_courier_courier')->delivery_time;
        $html = '';
        $html = '<select MULTIPLE="MULTIPLE" id="dpd_'.$name.'" class="option-control" style="width: 100px" value="" name="'.$this->getElement()->getName().'['.$name.']['.$i.'][]" >';
        $selected = '';
        foreach ($deliveryTime as $line => $time_strip)
        {
            if ($this->_getValue('time/'.$i) !== null)
                $selected = (in_array($line, $this->_getValue('time/'.$i)))?'selected':'';
            $html .= '<option value="'.$line.'" '.$selected.'>'.$time_strip.'</option>';
        }
        $html .= '</select>';
        return $html;
    }
}
